<?php
namespace process;

use app\controller\Order;
use Workerman\Crontab\Crontab;
use support\Db;

class Task
{
    public function onWorkerStart()
    {

        /**
         * 每天二点执行一次
         * 拉取学校账单
         */
        new Crontab('0 0 */1 * * *', function(){
            // 遍历学校列表
            $school_data = Db::table('charge_school')->get();
            $order_obj = new Order;
            foreach ($school_data as $sc_val) {
                $order_obj->fetch($sc_val->school_id, date('Y-m-d', strtotime('-1 day')));
            }
            echo date('Y-m-d').'拉取账单完成';
        });

        /**
         * 每天四点执行一次
         * 对账
         */
        new Crontab('0 0 */1 * * *', function(){
            // 遍历学校列表
            $school_data = Db::table('charge_school')->get();
            $order_obj = new Order;
            foreach ($school_data as $sc_val) {
                $order_obj->check($sc_val->school_id, date('Y-m-d', strtotime('-1 day')));
            }
            echo date('Y-m-d').'对账完成';
        });
    }
}