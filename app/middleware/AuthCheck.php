<?php
namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AuthCheck implements MiddlewareInterface
{
    public function process(Request $request, callable $handler) : Response
    {
        // $session = $request->session();
        // // 用户未登录
        // if (!$session->get('userinfo')) {
        //     // 拦截请求，返回一个重定向响应，请求停止向洋葱芯穿越
        //     return redirect('/user/login');
        // }
        // 请求继续向洋葱芯穿越
        
        try {
            $request->user = \Tinywan\Jwt\JwtToken::getExtend();
            return $handler($request);
        } catch (\Tinywan\Jwt\Exception\JwtTokenException $exception) {
            
            return json(['code' => 401, 'message' => '身份验证失败']);
        }

    }
}