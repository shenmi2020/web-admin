<?php

namespace app\controller;

use support\Request;
// use support\Db;
use app\model\School;
use support\Redis;
use think\facade\Db;
use Tinywan\Jwt\JwtToken;

class Index extends Base
{
    
    public function index(Request $request)
    {

        // $data = Db::table('wx_user')->select();
        // var_dump($data);

        $start_time = microtime(true) * 1000000;
        $start_memory = memory_get_usage();
        // $data = Db::table('person')->get();
        // foreach ($this->test_yield($data) as $val) {
        //     Db::table('person')->where('id', $val->id)->update([
        //         'update_time' => time()
        //     ]);
        // }
        // foreach ($this->test_yield() as $val) {
        //    echo $val."\n";
        // }
        $c = 0;
        for ($i = 1; $i < 1000; $i++) {
            // yield $i;
            $c = $c + $i;
        }
        // 
        $end_memory = memory_get_usage();
        $end_time = microtime(true) * 1000000;
        // 31139786 30965441 33289970
        // {"time":33338741,"memory":5563984}
        // {"time":31755205,"memory":5563984}
        
        return json([
            'time' => $end_time - $start_time,
            'memory1' => $end_memory,
            'memory2' => $start_memory,
            'data' => $c
        ]);
    }

    public function demo()
    {

        // $a = scandir('./');
        $a = glob('*.php');
        
        foreach ($a as $val) {
            echo file_get_contents($val);
        }

        return json([
            
            'data' => $a
        ]);
    }

    public function test_yield()
    {
        for ($i = 1; $i < 100000; $i++) {
            yield $i;
        }

    }

    /**
     * 导入学生信息
     */
    public function import()
    {

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load('./public/xls/aihuagzshuangyu.xlsx'); //载入excel表格

        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow(); // 总行数
        
        $data = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $id_card = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
            // $data[] = [
                
            //     is_string( $worksheet->getCellByColumnAndRow(1, $row)->getValue()),
            //     // str_replace(",", '', number_format($worksheet->getCellByColumnAndRow(1, $row)->getValue())),
            //     $worksheet->getCellByColumnAndRow(2, $row)->getValue(),
            //     $worksheet->getCellByColumnAndRow(3, $row)->getValue(),
            // ];
            $user_info = [
                'name' => trim($worksheet->getCellByColumnAndRow(4, $row)->getValue()),
                'grade' => trim($worksheet->getCellByColumnAndRow(2, $row)->getValue()),
                'class' => trim($worksheet->getCellByColumnAndRow(3, $row)->getValue()),
                'sex' => trim($worksheet->getCellByColumnAndRow(5, $row)->getValue()) == '男' ? 1 : 2,
                'id_card' => is_string($id_card) ? trim($id_card) : str_replace(",", '', number_format($id_card)),
                'number' => trim($worksheet->getCellByColumnAndRow(7, $row)->getValue()),
                'status' => trim($worksheet->getCellByColumnAndRow(8, $row)->getValue()),
                'category' => trim($worksheet->getCellByColumnAndRow(9, $row)->getValue()),
                'school' => '烟台爱华高中双语部',
                'user_id' => md5(uniqid(mt_rand(1, 9999), true) . mt_rand(1, 9999)),
                'create_time' => time()
            ];
            $data[] = $user_info;
            Db::table('person')->insert($user_info);
            // 
            
        }

        return $this->success($data);
        
    }

 
    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webman']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

    public function test()
    {
        $str = '';
        for ($i=0; $i < 16; $i++) { 
            # code...
            $rand_num = [
                mt_rand(48, 57),
                mt_rand(65, 90),
                mt_rand(97, 122)
            ];
            
            $str .= chr($rand_num[mt_rand(0, 2)]);
        }

        return json(['code' => 0, 'msg' => $str, 'rand' => mt_rand(0, 2)]);
    }

    public function test2()
    {
        // $num_0 = $num_1 = $num_2 = 0;
        $num_arr = [];
        for ($i=0; $i < 100000; $i++) { 
            $num = mt_rand(0, 2);
            if (isset($num_arr[$num])) {
                $num_arr[$num]++;
            } else {
                $num_arr[$num] = 1;
            }
        }

        return json(['code' => 0, 'msg' => array_values($num_arr)]);
    }

    public function test3()
    {
        $uid = JwtToken::getCurrentId();
        $data = JwtToken::getExtend();
        $data = JwtToken::getTokenExp();
        // $data = JwtToken::refreshToken();

        return json([
            'code' => 0,
            'data' => $data
        ]);
    }

    
}
