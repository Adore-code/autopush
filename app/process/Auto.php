<?php
namespace app\process;

use app\controller\ChatController;
use app\controller\CronTwitterController;
use Workerman\Crontab\Crontab;

class Auto
{
    public function onWorkerStart(): void
    {
        // 每分钟执行一次
        new Crontab('0 */3 * * * *', function(){
            $chat = new ChatController();
            $chat->getAiReply();
        });

        // 每分钟执行一次
        new Crontab('0 */5 * * * *', function(){
            $chat = new ChatController();
            $chat->getAllOpenTasks();
        });

        // 每分钟执行一次
        new Crontab('0 */2 * * * *', function(){
            $twitter = new CronTwitterController();
            $twitter->createArticle();
        });
    }
}