<?php

namespace app\controller;

class Base
{
    public function success($data = null, $msg = 'success', $code = 0)
    {
        return json(['code' => $code, 'data' => $data, 'msg' => $msg]);
    }

    public function error($msg = 'error', $code = 40000)
    {
        return json(['code' => $code, 'msg' => $msg]);
    }
}