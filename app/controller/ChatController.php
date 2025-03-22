<?php

namespace app\controller;

use support\Request;
use Webman\Openai\Chat;
use Workerman\Protocols\Http\Chunk;

class ChatController
{
    public function index(Request $request)
    {
        static $readme;
        if (!$readme) {
            $readme = file_get_contents(base_path('README.md'));
        }
        return $readme;
    }

    public function completions(Request $request)
    {
        // 创建 Chat 实例
        $chat = new Chat([
            'apikey' => "sk-7226589522c0454fac3d61a3b0db2817",
            'api' => 'https://api.deepseek.com'
        ]);

        // 发送请求
        $response = $chat->completions([
            'model' => 'deepseek-chat',
            'stream' => false, // 关闭流式输出
            'messages' => [['role' => 'user', 'content' => '你好！']],
        ]);

        // 检查 API 响应
        if (isset($response['error'])) {
            return json(['error' => $response['error']], 400);
        }

        // 返回完整的 JSON 响应
        return json($response);
    }
}
