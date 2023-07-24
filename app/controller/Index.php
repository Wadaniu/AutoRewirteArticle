<?php
/**
 * @copyright Copyright (c) 2021 勾股工作室
 * @license https://opensource.org/licenses/Apache-2.0
 * @link https://www.gougucms.com
 */
declare (strict_types = 1);
namespace app\controller;

use app\BaseController;
use app\middleware\Auth;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Db;

class Index extends BaseController
{
    /**
     * 控制器中间件 [登录、注册 不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' 	=> ['reg','login'] ]
    ];

    /**
     * @param $user_id
     * @return string
     */
    public function getToken($user_id){
        $time = time(); //当前时间
        $conf = $this->jwt_conf;
        $token = [
            'iss' => $conf['iss'], //签发者 可选
            'aud' => $conf['aud'], //接收该JWT的一方，可选
            'iat' => $time, //签发时间
            'nbf' => $time-1 , //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
            'exp' => $time+$conf['exptime'], //过期时间,这里设置2个小时
            'data' => [
                //自定义信息，不要定义敏感信息
                'userid' =>$user_id,
            ]
        ];
        return JWT::encode($token, $conf['secrect'], 'HS256'); //输出Token  默认'HS256'
    }

    /**
     * @param $token
     */
    public static function checkToken($token){
        try {
            JWT::$leeway = 60;//当前时间减去60，把时间留点余地
            $decoded = JWT::decode($token,  env('jwt.secrect'), ['HS256']); //HS256方式，这里要和签发的时候对应
            return (array)$decoded;
        } catch(\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
            return json(['code'=>403,'msg'=>'签名错误']);
        }catch(\Firebase\JWT\BeforeValidException $e) {  // 签名在某个时间点之后才能用
            return json(['code'=>401,'msg'=>'token失效']);
        }catch(\Firebase\JWT\ExpiredException $e) {  // token过期
            return json(['code'=>401,'msg'=>'token已过期']);
        }catch(Exception $e) {  //其他错误
            return json(['code'=>404,'msg'=>'非法请求']);
        }catch(\UnexpectedValueException $e) {  //其他错误
            return json(['code'=>404,'msg'=>'非法请求']);
        } catch(\DomainException $e) {  //其他错误
            return json(['code'=>404,'msg'=>'非法请求']);
        }

    }

    /**
     * @api {post} /index/index API页面
     * @apiDescription  返回首页信息
     */
    public function index()
    {
        $this->apiSuccess('请求成功',['list' => []]);
    }

    /**
     * @api {post} /index/login 会员登录
     * @apiDescription 系统登录接口，返回 token 用于操作需验证身份的接口

     * @apiParam (请求参数：) {string}             username 登录用户名
     * @apiParam (请求参数：) {string}             password 登录密码

     * @apiParam (响应字段：) {string}             token    Token

     * @apiSuccessExample {json} 成功示例
     * {"code":0,"msg":"登录成功","time":1627374739,"data":{"token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhcGkuZ291Z3VjbXMuY29tIiwiYXVkIjoiZ291Z3VjbXMiLCJpYXQiOjE2MjczNzQ3MzksImV4cCI6MTYyNzM3ODMzOSwidWlkIjoxfQ.gjYMtCIwKKY7AalFTlwB2ZVWULxiQpsGvrz5I5t2qTs"}}
     * @apiErrorExample {json} 失败示例
     * {"code":1,"msg":"帐号或密码错误","time":1627374820,"data":[]}
     */
    public function login()
    {
        $param = get_params();
        if(empty($param['username']) || empty($param['password'])){
            $this->apiError('参数错误');
        }
        // 校验用户名密码
        $user = Db::name('User')->where(['username' => $param['username']])->find();
        if (empty($user)) {
            $this->apiError('帐号或密码错误');
        }
        $param['pwd'] = set_password($param['password'], $user['salt']);
        if ($param['pwd'] !== $user['password']) {
            $this->apiError('帐号或密码错误');
        }
        if ($user['status'] == -1) {
            $this->apiError('该用户禁止登录,请于平台联系');
        }
        $data = [
            'last_login_time' => time(),
            'last_login_ip' => request()->ip(),
            'login_num' => $user['login_num'] + 1,
        ];
        $res = Db::name('user')->where(['id' => $user['id']])->update($data);
        if ($res) {
            $token = self::getToken($user['id']);
            add_user_log('api', '登录');
            $this->apiSuccess('登录成功', ['token' => $token]);
        }
    }

    /**
     * @api {post} /index/reg 会员注册
     * @apiDescription  系统注册接口，返回是否成功的提示，需再次登录

     * @apiParam (请求参数：) {string}             username 用户名
     * @apiParam (请求参数：) {string}             password 密码

     * @apiSuccessExample {json} 成功示例
     * {"code":0,"msg":"注册成功","time":1627375117,"data":[]}
     * @apiErrorExample {json} 失败示例
     * {"code":1,"msg":"该账户已经存在","time":1627374899,"data":[]}
     */
    public function reg()
    {
        $param = get_params();
        if(empty($param['username']) || empty($param['pwd'])){
            $this->apiError('参数错误');
        }
        $user = Db::name('user')->where(['username' => $param['username']])->find();
        if (!empty($user)) {
            $this->apiError('该账户已经存在');
        }
        $param['salt'] = set_salt(20);
        $param['password'] = set_password($param['pwd'], $param['salt']);
        $param['register_time'] = time();
        $param['headimgurl'] = '/static/admin/images/icon.png';
        $param['register_ip'] = request()->ip();

        $uid = Db::name('User')->strict(false)->field(true)->insertGetId($param);
        if($uid){
            add_user_log('api', '注册');
            $this->apiSuccess('注册成功,请登录');
        }else{
            $this->apiError('注册失败');
        }
    }

    /**
     * @api {post} /index/demo 测试页面
     * @apiDescription  返回文章列表信息

     * @apiParam (请求参数：) {string}  token Token

     * @apiSuccessExample {json} 响应数据样例
     * {"code":1,"msg":"","time":1563517637,"data":{"id":13,"email":"test110@qq.com","password":"e10adc3949ba59abbe56e057f20f883e","sex":1,"last_login_time":1563517503,"last_login_ip":"127.0.0.1","qq":"123455","mobile":"","mobile_validated":0,"email_validated":0,"type_id":1,"status":1,"create_ip":"127.0.0.1","update_time":1563507130,"create_time":1563503991,"type_name":"注册会员"}}
     */
    public function demo()
    {
        $uid = JWT_UID;
        $userInfo = Db::name('User')->where(['id' => $uid])->find();
        $this->apiSuccess('请求成功', ['user' => $userInfo]);
    }
}
