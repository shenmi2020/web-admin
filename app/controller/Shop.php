<?php

namespace app\controller;

use support\Request;
use app\model\Shop as ShopModel;

class Shop extends Base
{
    /**
     * 列表
     */
    public function index(Request $request)
    {
        $pageIndex = $request->get('pageIndex', 1);
        $pageSize = $request->get('pageSize', 20);
        // $data = ShopModel::get();
        $data = [$pageIndex, $pageSize];
        
        return $this->success($data);
    }

}
