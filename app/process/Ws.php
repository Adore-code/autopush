<?php
namespace app\process;

use plugin\autopush\app\controller\WsController;
use Workerman\Worker;
use Workerman\Timer;

class Ws
{
    public function onWorkerStart()
    {
        $ws = new Worker('websocket://0.0.0.0:2346');
        $ws->name = 'ws-chat';
        $ws->count = 1;

        $ws->onConnect = function ($connection) {
            echo "WebSocket连接建立: {$connection->id}\n";

            // 设置上次活跃时间
            $connection->__last_active = time();

            // 设置定时器检查是否超时（每 60 秒检查一次）
            $connection->timer_id = Timer::add(60, function () use ($connection) {
                if (time() - $connection->__last_active > 600) {
                    $connection->send(json_encode([
                        'type' => 'close',
                        'message' => '连接超时，已自动断开（10分钟无操作）'
                    ], JSON_UNESCAPED_UNICODE));
                    $connection->close();
                }
            });
        };

        $ws->onMessage = function ($connection, $data) {
            // 每次消息更新活跃时间
            $connection->__last_active = time();

            // 消息交给业务控制器处理
            WsController::onMessage($connection, $data);
        };

        $ws->onClose = function ($connection) {
            echo "WebSocket连接关闭: {$connection->id}\n";

            // 清理定时器
            if (isset($connection->timer_id)) {
                Timer::del($connection->timer_id);
            }
        };

        $ws->listen();
    }
}
