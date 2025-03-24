<?php
namespace app\process;

use Workerman\Crontab\Crontab;

class Auto
{
    public function onWorkerStart(): void
    {

        // 每5秒执行一次
        new Crontab('*/5 * * * * *', function(){
            echo date('Y-m-d H:i:s')."\n";
        });

        // 每分钟执行一次
        new Crontab('0 */1 * * * *', function(){
            echo date('Y-m-d H:i:s')."\n";
        });
    }
}