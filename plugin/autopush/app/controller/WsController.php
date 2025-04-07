<?php

namespace plugin\autopush\app\controller;


use support\Request;
use support\think\Db;
use Workerman\Connection\TcpConnection;
use Webman\Openai\Chat;

class WsController
{
    public function wsSign(Request $request)
    {
        $userId = admin_id();

        if(!$userId) return json([
            'code' => 1,
            'msg' => '您没有权限访问该接口！',
            'ws_url' => ''
        ]);

        if (!$userId) {
            return json(['code' => 400, 'msg' => 'user_id 不能为空']);
        }

        $timestamp = time();
        $secret = 'your_super_secret_key';
        $sign = md5($userId . $timestamp . $secret);

        $wsUrl = "wss://autopush.pro/ws/chat?user_id=$userId&timestamp=$timestamp&sign=$sign";

        return json([
            'code' => 0,
            'msg' => 'ok',
            'ws_url' => $wsUrl
        ]);
    }

    public static function onMessage(TcpConnection $connection, $data)
    {
        $data = json_decode($data, true);

        $message = trim($data['message'] ?? '');
        $model = $data['model'] ?? 'gpt-3.5-turbo';
        $role = $data['role'] ?? 'default';
        $provider = $data['provider'] ?? 'openai';
        $stream = $data['stream'] ?? true;
        $userId = $connection->id;

        if (!$message) {
            $connection->send(json_encode(['type' => 'error', 'message' => '消息为空']));
            return;
        }

        [$baseUrl, $apiKey] = self::getProviderConfig($provider);

        $chat = new Chat(['apikey' => $apiKey, 'api' => $baseUrl]);

        // 使用连接内存上下文（不依赖 Redis）
        if (!property_exists($connection, '__chat_context') || !is_array($connection->__chat_context)) {
            $connection->__chat_context = [];
        }

        $history = array_slice($connection->__chat_context, -10);
        $messages = array_merge($history, [['role' => 'user', 'content' => $message]]);

        $responseText = '';
        $startTime = date('Y-m-d H:i:s');

        if (empty($history)) {
            $prompt = match ($role) {
                'coder' => '你是一个经验丰富的程序员，擅长用中文解释技术问题。',
                'writer' => '你是一个擅长中文写作的作家，文笔优美，逻辑清晰。',
                'support' => '你是一个耐心的客服人员，用中文回答用户的问题。',
                default => '你是一个智能助手，使用中文进行回答。'
            };
            $messages = array_merge($history, [['role' => 'system', 'content' => $prompt]]);
        }

        $chat->completions([
            'model' => $model,
            'stream' => true,
            "stream_options" => ["include_usage" => true],
            'messages' => array_merge([
                ['role' => 'system', 'content' => '你是一个' . $role]
            ], $messages),
        ], [
            'stream' => function ($delta) use ($connection, &$responseText, $userId) {
                if (isset($delta['choices'][0]['delta']['content'])) {
                    $word = $delta['choices'][0]['delta']['content'];
                    $responseText .= $word;
                    $connection->send(json_encode([
                        'type' => 'stream',
                        'content' => $word,
                        'user_id' => $userId,
                        'timestamp' => date('Y-m-d H:i:s')
                    ], JSON_UNESCAPED_UNICODE));
                }
            },
            'complete' => function ($result, $response) use ($connection, $userId, $model, $provider, $message, &$responseText, $startTime, $role) {
                $usage = $result['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
                $promptTokenEstimate = intval(strlen($message) / 4);
                $completionTokenEstimate = intval(strlen($responseText) / 4);
                $totalTokenEstimate = $promptTokenEstimate + $completionTokenEstimate;

                $usage = $result['usage'] ?? [
                    'prompt_tokens' => $promptTokenEstimate,
                    'completion_tokens' => $completionTokenEstimate,
                    'total_tokens' => $totalTokenEstimate,
                ];
                // 写入上下文（仅保留最后20条）
                $connection->__chat_context[] = ['role' => 'user', 'content' => $message];
                $connection->__chat_context[] = ['role' => 'assistant', 'content' => $responseText];
                if (count($connection->__chat_context) > 20) {
                    $connection->__chat_context = array_slice($connection->__chat_context, -20);
                }

                // 写入数据库日志
                Db::name('wa_chat_logs')->insert([
                    'user_id' => 2,
                    'role' => $role,
                    'question' => $message,
                    'answer' => $responseText,
                    'model' => $model,
                    'provider' => $provider,
                    'tokens_prompt' => $usage['prompt_tokens'],
                    'tokens_completion' => $usage['completion_tokens'],
                    'tokens_total' => $usage['total_tokens'],
                    'created_at' => $startTime
                ]);

                // 返回完成标志
                $connection->send(json_encode([
                    'type' => 'done',
                    'usage' => $usage,
                    'timestamp' => date('Y-m-d H:i:s')
                ], JSON_UNESCAPED_UNICODE));
            }
        ]);
    }

    private static function getProviderConfig(string $provider): array
    {
        return match ($provider) {
            'openai'   => ['https://api.openai.com', getenv('OPENAI_API_KEY')],
            'deepseek' => ['https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions', getenv('BAILIAN_API_KEY')],
            default    => ['', '']
        };
    }
}