<?php
// 应用公共文件

//获取url参数
use think\facade\Cache;
use think\facade\Request;

function get_params($key = "")
{
    return Request::instance()->param($key);
}

//随机字符串，默认长度10
function set_salt($num = 10)
{
    $str = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
    $salt = substr(str_shuffle($str), 10, $num);
    return $salt;
}

//密码加密
function set_password($pwd, $salt)
{
    return md5(md5($pwd . $salt) . $salt);
}

/**
 * 客户操作日志
 * @param string $type 操作类型 login reg add edit view delete down join sign play order pay
 * @param string    $param_str 操作内容
 * @param int    $param_id 操作内容id
 * @param array  $param 提交的参数
 */
function add_user_log($type, $param_str = '', $param_id = 0, $param = [])
{
    $request = request();
    $title = '未知操作';
    if(!empty($type)){
        $title = $type;
    }
    if ($type == 'login') {
        $login_user = \think\facade\Db::name('User')->where(array('id' => $param_id))->find();
        if ($login_user['nickname'] == '') {
            $login_user['nickname'] = $login_user['name'];
        }
        if ($login_user['nickname'] == '') {
            $login_user['nickname'] = $login_user['username'];
        }
    } else {
        $login_user = get_login_user();
        if (empty($login_user)) {
            $login_user = [];
            $login_user['id'] = 0;
            $login_user['nickname'] = '游客';
        } else {
            if ($login_user['nickname'] == '') {
                $login_user['nickname'] = $login_user['username'];
            }
        }
    }
    $content = $login_user['nickname'] . '在' . date('Y-m-d H:i:s') . '执行了' . $title . '操作';
    if ($param_str != '') {
        $content = $login_user['nickname'] . '在' . date('Y-m-d H:i:s') . $title . '了' . $param_str;
    }
    $data = [];
    $data['uid'] = $login_user['id'];
    $data['nickname'] = $login_user['nickname'];
    $data['type'] = $type;
    $data['title'] = $title;
    $data['content'] = $content;
    $data['param_id'] = $param_id;
    $data['param'] = json_encode($param);
    $data['module'] = strtolower(app('http')->getName());
    $data['controller'] = strtolower(app('request')->controller());
    $data['function'] = strtolower(app('request')->action());
    $data['ip'] = app('request')->ip();
    $data['create_time'] = time();
    \think\facade\Db::name('UserLog')->strict(false)->field(true)->insert($data);
}

function get_login_user($key = "")
{
    $session_user = get_cache('app.session_user');
    if (\think\facade\Session::has($session_user)) {
        $gougu_user = \think\facade\Session::get($session_user);
        if (!empty($key)) {
            if (isset($gougu_user[$key])) {
                return $gougu_user[$key];
            } else {
                return '';
            }
        } else {
            return $gougu_user;
        }
    } else {
        return '';
    }
}

//设置缓存
function set_cache($key, $value, $date = 86400)
{
    Cache::set($key, $value, $date);
}

//读取缓存
function get_cache($key)
{
    return Cache::get($key);
}

//清空缓存
function clear_cache($key)
{
    Cache::clear($key);
}
