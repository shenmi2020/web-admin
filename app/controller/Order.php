<?php

namespace app\controller;

use support\Request;
use support\Db;

class Order extends Base
{

    public $iv = 'AED7966C958C149632180F51EDBC9313';
    public $weixiao_private = '30820278020100300D06092A864886F70D0101010500048202623082025E02010002818100DDC2754B64A6B16A41601BEB7F40699922F2A22CB73741C0589475FA984BC08E6075DEB733F1EDC71DD15AEC28898EF32E13BB6BD6AEFE9F5F78D556C258F09C58EB932005449263A97F11569156A41DE0178E6A2F80F9CF4D82B7C0749B4658D27EB63F304F9186DEDA0F08C724424A19908C800019921FFE793B813AD39AAD02030100010281803837A1D09915810874C64E8DA6D6C76E60E3ADA5345537BFF134C1ABE38BE0A6B7616A327B62AB6ABCEE63E4566A78E8C117937DC510DBCFBF3E3CA71FE1B82D11B0EC968E6E7A233BC6FDD49768C5694696404C0268BAF5A1D5F833876D47AE87C052F9895AF448A755CC66E20972B9C8E1DB4A6C93BD88C88AC29CD2C6A2A9024100F343FDC66BF142A32114453DE897F65D930E6CC6ECF9DED8B286CC4C196FC4D90626810D08B5F1DB0FEC1EB37E00238EF4ACB3F2015174B44CAAF35C69C7263F024100E95E43B9064F83210DE2406071CEA9090F8FF19EC7CD319BC63A1BD09145D66F51D51192BB35320767489B8B456053513B158557E9AB546BF09E5FB1D8AB3C13024100A741E4467D09108C20BE532D51B2CA0D6482D27FA387D9949C8ADA04A8A8946BB332DE201C111D0D45514F7A91F37E7F57F33675FA3A0B47BC3EFDBC586E38F9024100DBE966AC2F14329FAD73ADF2B48C68A20F36381CC66FC8F5E060D5E13F64AE640C9B5A8A093C61BEB447A9BC1E4E5D7548D648E7C55D1C9AF30E6B632EA87E5D024100D5F9A5CB89621A68B5DF4D262D99907914AD7DAF31C72FBC822A998DEEE63A5A6A14C67A7A2E70BCCBEAE7E077479B4FD264B769E90F69C41B01A54710030D23';

    public function test()
    {
        $this->fetch(10003, '2023-02-16');
    }

    // 拉取云平台账单
    public function fetch($school_id = 10003, $date = '2023-02-16')
    {
      
        $school_info = Db::table('charge_school')->where('school_id', $school_id)->first();
        if (empty($school_info)) {
            $this->log('学校id不存在:' . $school_id, 'school_id:' . $school_id . '|日期:' . $date);
            return;
        }
        $url = 'https://weixiao.dmlinker.com/wechat/charge.user/bankOrder';
        $req_param = [
            'school_id' => $school_id,
            'date' => $date
        ];
        $result = $this->curl_get($url, $req_param);
        $result = json_decode($result, true);
        if (empty($result) || empty($result['code'])) {
            $this->log($date . $school_info->name . '云平台账单拉取异常', $result['msg'] ?? '');
            return;
        }
        // 删除平台旧账单
        Db::table('charge_order')->where('school_id', $school_id)->where('check_date', $date)->where('delete_time', NULL)->update([
            'delete_time' => date('Y-m-d H:i:s')
        ]);
        $data = [];
        foreach ($result['data'] as $val) {
            $data[] = [
                'order_id' => $val['order_id'],
                'pay_money' => $val['pay'],
                'status' => 4,
                'order_no' => $val['order_no'],
                'acq_trace' => $val['bank_number'],
                'user_name' => $val['student_name'],
                'card_number' => $val['card_number'],
                'school_id' => $school_id,
                'pay_time' => $val['create_time'],
                'check_date' => $date
            ];
        }
        Db::table('charge_order')->insert($data);
        $this->log($date . $school_info->name . '云平台账单拉取完成');
    }
    
    /**
     * 对账
     * 对账文件格式说明
        0: "2023021617421667667597 "
        1: " G20210005 "
        2: " 赵海潇 "
        3: "  "
        4: " 0.00 "
        5: " 0.00 "
        6: " 0.01 "
        7: "  "
        8: " 商品收费:0.01元 "
        9: "  "
        10: " 00480001 "
        11: "    "
        12: " 17:51:45 "
        13: " "
     */
    public function check($school_id = 10003, $date = '2023-02-16')
    {
        $school_info = Db::table('charge_school')->where('school_id', $school_id)->first();
        if (empty($school_info)) {
            $this->log('学校id不存在:' . $school_id, 'school_id:' . $school_id . '|日期:' . $date);
            return;
        }
        // 对账日期
        $formart_date = date('Ymd', strtotime($date));
        $file = 'public/dz' . $formart_date . '00016000' . $school_info->merchid . '.txt';
        
        if (file_exists($file)) {
            $fp = fopen($file, 'r');
            if (empty(filesize($file))) {
                return;
            }
            $str = fread($fp, filesize($file));
            $str = json_decode($str, true);
            
            $result = $this->deRsaSign($str['cipherText'], $str['skey']);
            $result = str_replace("\n", "\\n", $result);
            $result = json_decode($result, true);
            $result = explode("\n", $result['plainText']);
            $result = array_filter($result);
            $data = [];
            foreach ($result as $val) {
                // 过滤掉非学杂费格式的账单
                if (substr_count($val, '|') != 13) {
                    continue;
                }
                $data[] = explode('|', $val);
            }
            // 处理账单
            foreach ($data as $val) {
                $order_info = Db::table('charge_order')
                    ->where('school_id', $school_id)
                    ->where('check_date', $date)
                    ->where('delete_time', NULL)
                    ->where('status', '<>', 3)
                    ->where('acq_trace', trim($val[0]))
                    ->first();
                if (empty($order_info)) {
                    // 云平台缺少订单
                    Db::table('charge_order')->insert([
                        'real_money' => $val[6],
                        'status' => 3,
                        'acq_trace' => $val[0],
                        'card_number' => trim($val[1]),
                        'school_id' => $school_id,
                        'create_time' => date('Y-m-d H:i:s'),
                        'check_date' => $date
                    ]);
                } else {
                    // 比对账单金额
                    if ($order_info->pay_money == $val[6]) {
                        // 正常
                        $status = 2;
                    } else {
                        // 金额不匹配
                        $status = 5;
                    }
                    Db::table('charge_order')->where('id', $order_info['id'])->update([
                        'status' => $status,
                        'real_money' => $val[6],
                        'create_time' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            $this->log($date . $school_info->name . '对账完成');
        } else {
            // 对账文件不存在
            $this->log($date . $school_info->name . '对账文件不存在');
        }
    }

    /**
     * 重新对账
     */
    public function recheck(Request $request)
    {
        $param = $request->all();

        return $this->success($param);
    }


    /**
     * 请求方法
     */
    public function curl_get($url, $params = array(), $timeout = 50)
    {
        $url = "{$url}?" . http_build_query ( $params );
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 60 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $params );
        $result = curl_exec ( $ch );
        curl_close ( $ch );

        return $result;
    }

    /**
     * 日志
     */
    public function log($title = '', $content = '')
    {
        Db::table('charge_log')->insert([
            'title' => $title,
            'content' => $content,
            'create_time' => date('Y-m-d H:i:s')
        ]);
    }

    public function deRsaSign($cipherText, $skey)
    {
        // 解密 skey 得到对称密钥
        $de_skey = $this->decryptRsa($skey, $this->weixiao_private);

        // 使用对称密钥解密cipherText
        $de_cipherText = $this->decryptAes($cipherText, $de_skey, $this->iv);
        return $de_cipherText;
        // 获取
        $plain_text = json_decode($de_cipherText, true);
        // var_dump($de_cipherText);
        if (!isset($plain_text['plainText']) || !isset($plain_text['sign'])) {
            return false;
        }
        // 服务方使用请求方公钥验证签名
        // $result = $this->verifySign($plain_text['plainText'], $plain_text['sign'], $this->bank_public);
        
        // if (!$result) {
        //     return false;
        // }
        return $plain_text['plainText'];
    }

    /**
     * 非对称解密
     * @param string $data 加密内容
     * @param string $key 服务方私钥解密
     */
    public function decryptRsa($data, $key)
    {
        $data = hex2bin($data);
        // $key = base64_encode(hex2bin($key));
        // $key = chunk_split($key, 64, "\n");
        // $key = "-----BEGIN RSA PRIVATE KEY-----\n$key-----END RSA PRIVATE KEY-----\n";
        // var_dump($key);
        $key = '-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCkAQOUGkXtoPg8
p9qQuRbw6JxIUAmTtHZEGXAicBNR23lzHK7VMDi3x1MIiyYISRRgQE92pn/XmGhr
zz19NnR2xiO5/Kx0tPRwSyfBoosf/n2evAQ8a6lEDNIR2/90HjdfPAn8Y7K0p1OI
s+xQp1bNcafe7hnsneQ+USeKKaj/RWn/zLoKI1kWFhoK6SEa2sGtQ/FezxICUbCD
bO76k1ZqjXEpOgkzsR4HE+90mD+m7jFCco1mgZ8/Ayo5eULu7xjQKCTTILx9tqUr
qKlQFSqgCgqv4VywdbxoLqIv34KB414EWpvXkblcDynZ6K/uW13Dtgo5jJs8JuUF
UP+UvoGHAgMBAAECggEADMHvb1Pj4KpG5SEBhYSAXlkZ3x4qwIynLoD0Ehm5xwJV
nji6+OZ5YwJkWSPJ35cfuKUICWjGRRUb+lbyp4zW3m5nVQ5ss99nrFyMSSnFvMVl
LDXf9ntBfYOpy63bX0MCd6wJ8tImkpr5iobEeTmrLOwMbPEEnz1hBd/2PW8kMEcs
G0dCAJWDX5DRyDdhzh0rEnylJP/V549/NKI+3TjUu3sJq4AFCDh9Oo0uucLT6rBZ
qlmimJ5SVsQiZyB6752glazRIZ23H9ZiggjrUc2qEPWP8sRn4ZVxOdlEUKL2pS/f
zwFYaCqiltEFJvwUsMpTodpIbYTwMxGUgx3dwbeMIQKBgQDZDW0SK8uLD0x1l1+6
1oRPhuOCUMRpOLKJsAgQSXCLlqMc4J7ClDz3Qhh/kNsn0y0tYK0hgUL+seGE8clk
HeA0K2dxABzGqHhBbV1WfnxNWGt8ZjQVXVAyDUU8mrcf5dnVbiQcXlGiMq7lWOuC
DnG7CJuz2EngzfoxMGv/XHX3mQKBgQDBbr1k/TMmQGz6EUGXAGWm5M8gvHEnkAEI
sJJj/cfNuYNt95Lpz+LBFJqOkwOcuOzizgVzA22udOdWLVRfxBMAMmQqHGNu+dNP
7++pdHKbQ9JVOeQunoEX5hXHghyrt1Qaq6W0hqfmFAD1qjRVGYJe7joPSMmFgKpD
UDSZZh12HwKBgA3M+71nCXcbDup/KHgRwbHoyrhzeDmUgE2e4rReZwiJGG/ynEWU
9VdnXXVm+XhLxhiXiAqUVHUrTEKOuRZji+jlRZt6vVmoRpUqZf/k5PRqBdOQEAm3
uCymiVt0HuapT7NxYFxpZtlgTZyJjdfkITkaMAQ8YV4o2pqcEJHZCCspAoGBAIqD
lFhHAGO56s+/n6pT/Hbgjnowtw7Pjg388zdrObLVz4nlqWyJEyWUbYD/Qazut6NK
SJitsdMln6sUVsElFT4k15lYLtP/ThSGCqbb3l3U2T9ybzX7BxJoDtyJDaLhavaW
R9jYPE8DsBQ7R7JQzAzSpvze8IALPOFrA999QkedAoGAUQ4IcDY6ToJxaiMclpVA
eM4iCJ6oe1GxQGT6evyENCnuSuSiYcoV2Ys02pIAPrFGI4mlEHA3YquSPrxFigyW
vlwxuGeGCMbctJquaqTXIHH+Az9XeFMLq2uyovKkT/UAy4EDW7/wKuFPqlY+sIEr
uyR4ue9K5cI7LTpvKkv73p0=
-----END PRIVATE KEY-----';
        // var_dump($key);
        openssl_private_decrypt($data, $decrypted, $key);
       
        $decrypted = iconv('gbk', 'UTF-8', $decrypted);
        
        return $decrypted;
    }

    /**
     * 对称解密
     * @param string $data 加密内容
     * @param string $key 密钥 $key = '9B6C238C991BCA09543CB926C43B3DE4';
     * @param string $iv 向量 $iv = 'AED7966C958C149632180F51EDBC9313';
     */
    public function decryptAes($data, $key, $iv)
    {
        $data = hex2bin($data);
        $key = hex2bin($key);
        $iv = hex2bin($iv);
      
        $result = openssl_decrypt($data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $result = iconv('gbk', 'UTF-8', $result);

        return $result;
    }

    /**
     * 验证签名
     * @param string $data 签名内容
     * @param string $sign 签名
     * @param string $pub_key 请求方公钥
     */
    public function verifySign($data, $sign, $pub_key)
    {
        $data = iconv('UTF-8', 'GB2312', $data);
        $sign = hex2bin($sign);
        $pub_key = base64_encode(hex2bin($pub_key));
        $pub_key = chunk_split($pub_key, 64, "\n");
        $pub_key = "-----BEGIN PUBLIC KEY-----\n$pub_key-----END PUBLIC KEY-----\n";
        $result = openssl_verify($data, $sign, $pub_key) === 1;

        return $result;
    }
}