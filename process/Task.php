<?php
namespace process;

use Workerman\Crontab\Crontab;

class Task
{
    public function onWorkerStart()
    {

        // 每分钟执行一次
        new Crontab('0 */1 * * * *', function(){
            echo date('Y-m-d H:i:s'). '---1---'."\n";
        });

        // 每5分钟执行一次
        new Crontab('0 */5 * * * *', function(){
            echo date('Y-m-d H:i:s') . '---5---' ."\n";
        });
    }
}