<?php

namespace app\controller;

use support\Request;
// use support\Redis;
use Tinywan\Jwt\JwtToken;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Webman\RedisQueue\Redis;

class User extends Base
{

    public function index()
    {
        $user = [
            'id'  => 2022, // 这里必须是一个全局抽象唯一id
            'name'  => 'Tinywan',
            'email' => 'Tinywan@163.com'
        ];
        $token = JwtToken::generateToken($user);
        // var_dump(json_encode($token));
        return json([
            'time' => time(),
            'msg' => $token
        ]);
    }

    public function test4()
    {
        $data = JwtToken::refreshToken();
        return json([
            'code' => 0,
            'data' => $data
        ]);
    }

    public function test5()
    {
        $ms = 604800 / 3600 / 24;
        // for ($i=0; $i<10000000; $i++) {
        //     // Redis::set('random_' . $i, mt_rand(0, 9999999));
        //     // list($ms, $s) = explode(' ', microtime());
        //     $result[] = str_pad(mt_rand(1, 99999), 5, 0) . str_pad(mt_rand(1, 99999), 5, 0) . microtime();
        // }

        // $data = array_unique($result);

        return json([
            'code' => 0,
            // 'data' => $data,
            // 'result' => $result,
            'count' => $ms,
        ]);
    }

    public function export($request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Hello World !');

        $writer = new Xlsx($spreadsheet);
        $file_path = public_path().'/hello_world.xlsx';
        // 保存文件到 public 下
        $writer->save($file_path);
        // 下载文件
        return response()->download($file_path, '文件名.xlsx');
    }

    public function export2()
    {
        $spreadsheet = new Spreadsheet();
       
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Hello World !');

        $writer = new Xlsx($spreadsheet);
        $response = response();
        ob_start();
        $writer->save('php://output');
        $c = ob_get_contents();
        ob_flush();
        flush();
        $response->withHeaders([
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment;filename="xxx.xlsx"',
            'Cache-Control' => 'max-age=0',
        ])->withBody($c);
        return $response;
    }

    public function queue()
    {
        // 队列名
        $queue = 'send-mail';
        // 数据，可以直接传数组，无需序列化
        $data = ['to' => 'tom@gmail.com', 'content' => 'hello'];
        // 投递消息
        Redis::send($queue, $data);

        return response('redis queue test');
    }








}