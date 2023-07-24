<?php
use think\facade\Request;
use think\facade\Env;
use think\facade\Config;
// 应用公共文件
function get_params($key = "")
{
    return Request::instance()->param($key);
}


function returnToJson($code = 0, $msg = '请求成功', $data = [], $httpCode = 200, $header = [], $options = [])
{
    $res['code'] = $code;
    $res['msg'] = $msg;
    if (is_object($data)) {
        $data = $data->toArray();
    }
    if (!empty($data['total'])) {
        $res['count'] = $data['total'];
    } else {
        $res['count'] = 0;
    }
    $res['data'] = $data;
    $response = \think\Response::create($res, "json", $httpCode, $header, $options);
    throw new \think\exception\HttpResponseException($response);
}


function get_config($key)
{
    return Config::get($key);
}
