<?php

namespace app\controller;

use support\Request;
use support\Db;
use app\model\School;

class Index extends Base
{
    
    public function index(Request $request)
    {
        // $data = Db::table('user')->get();
        // $data = School::get();
        $user = [
            'id'  => 2022, // 这里必须是一个全局抽象唯一id
            'name'  => 'Tinywan',
            'email' => 'Tinywan@163.com'
        ];
      

        return $data = $this->error($request->user, 500);
    }

    public function push()
    {
        $user = [
            'id'  => 202212, // 这里必须是一个全局抽象唯一id
            'name'  => 'Tinywan',
            'email' => 'kingking@163.com'
        ];
        $token = \Tinywan\Jwt\JwtToken::generateToken($user);
      
        return $data = $this->error($token, 500);
    }

    public function demo()
    {
        // var_dump(A);
        // $data = \Tinywan\Jwt\JwtToken::getCurrentId();
        $data = [
            'exp' => \Tinywan\Jwt\JwtToken::getTokenExp(),
            'data' => \Tinywan\Jwt\JwtToken::getExtend()
        ];
        return $data = $this->error($data, 500);
    }

    public function ref()
    {
        $token = \Tinywan\Jwt\JwtToken::refreshToken();
        return $data = $this->error($token, 500);
    }

    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webman']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
