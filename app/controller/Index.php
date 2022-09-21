<?php

namespace app\controller;

use support\Request;
use support\Db;
use app\model\School;

class Index extends Base
{
    
    public function index(Request $request)
    {
        // return $data = $this->error($request->user, 500);
        return json(['hello']);
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
