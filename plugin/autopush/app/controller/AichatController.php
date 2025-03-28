<?php
namespace plugin\autopush\app\controller;

use support\Request;
use support\Response;
use support\think\Db;
use Webman\Openai\Chat;
use Workerman\Protocols\Http\Chunk;

class AichatController
{
    public function index()
    {
        return view('aichat/chat', [
            'title' => 'AI门通行证',
        ]);
    }

    public function chat(Request $request): Response
    {
        //if (isExpired()) return view('/plugin/autopush/public/demos/expired');

        if ($request->isPost()) {
            $post = json_decode($request->rawBody(), true);
            $provider = $post['provider'] ?? 'openai';
            $model = $post['model'] ?? 'gpt-3.5-turbo';
            $message = $post['message'] ?? '';
            $roleType = $post['role'] ?? 'default';

            [$baseUrl, $apiKey] = self::getProviderConfig($provider);
            $url = "$baseUrl/v1/chat/completions";

            // session 初始化
            $sid = session('sid');
            if (!$sid) {
                $sid = bin2hex(random_bytes(16));
                session()->set('sid', $sid);
            }

            $sessionKey = 'chat_history_' . $roleType;
            $history = session($sessionKey) ?? [];

            if (empty($history)) {
                $prompt = match ($roleType) {
                    'coder' => '你是一个经验丰富的程序员，擅长用中文解释技术问题。',
                    'writer' => '你是一个擅长中文写作的作家，文笔优美，逻辑清晰。',
                    'support' => '你是一个耐心的客服人员，用中文回答用户的问题。',
                    default => '你是一个智能助手，使用中文进行回答。'
                };
                $history[] = ['role' => 'system', 'content' => $prompt];
            }

            $history[] = ['role' => 'user', 'content' => $message];

            // 保存用户消息
            Db::name('wa_chat_logs')->save([
                'session_id' => $sid,
                'role' => 'user',
                'role_type' => $roleType,
                'user_id' => admin_id(),
                'content' => $message
            ]);

            // 构造请求数据
            $payload = [
                'model' => $model,
                'messages' => $history
            ];

            // curl 请求
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Bearer $apiKey"
                ],
                CURLOPT_POSTFIELDS => json_encode($payload)
            ]);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if (!$response) {
                return json(['code' => 1, 'msg' => '请求失败: ' . $error]);
            }

            $res = json_decode($response, true);
            $reply = $res['choices'][0]['message']['content'] ?? '请求失败';
            $usage = $res['usage'] ?? [];

            $history[] = ['role' => 'assistant', 'content' => $reply];
            session()->set($sessionKey, $history);

            // 保存助手回复
            Db::name('wa_chat_logs')->save([
                'session_id' => $sid,
                'role' => 'assistant',
                'role_type' => $roleType,
                'user_id' => admin_id(),
                'content' => $reply
            ]);

            return json(['code' => 0, 'msg' => 'ok', 'data' => ['reply' => $reply, 'usage' => $usage]]);
        }

        return view('aichat/chat', [
            'title' => 'AI门通行证',
        ]);
    }

    public function stream(Request $request): Response
    {
        $connection = $request->connection;

        $post = json_decode($request->rawBody(), true);
        $provider = $post['provider'] ?? 'openai';
        $model = $post['model'] ?? 'gpt-3.5-turbo';
        $message = $post['message'] ?? '';
        $roleType = $post['role'] ?? 'default';
        $user_id  = admin_id();
        [$baseUrl, $apiKey] = self::getProviderConfig($provider);

        $sid = $request->session()->get('sid') ?: bin2hex(random_bytes(16));
        $request->session()->set('sid', $sid);

        $sessionKey = 'chat_history_' . $roleType;
        $history = $request->session()->get($sessionKey) ?? [];

        if (empty($history)) {
            $prompt = match ($roleType) {
                'coder' => '你是一个经验丰富的程序员，擅长用中文解释技术问题。',
                'writer' => '你是一个擅长中文写作的作家，文笔优美，逻辑清晰。',
                'support' => '你是一个耐心的客服人员，用中文回答用户的问题。',
                default => '你是一个智能助手，使用中文进行回答。'
            };
            $history[] = ['role' => 'system', 'content' => $prompt];
        }

        $history[] = ['role' => 'user', 'content' => $message];

        // 保存用户消息
        Db::name('wa_chat_logs')->save([
            'session_id' => $sid,
            'role' => 'user',
            'role_type' => $roleType,
            'content' => $message,
            'user_id' => admin_id()
        ]);

        $reply = '';

        // 启动 Chat 流
        $chat = new Chat([
            'apikey' => $apiKey,
            'api' => $baseUrl
        ]);

        $chat->completions([
            'model' => $model,
            'stream' => true,
            'messages' => $history
        ], [
            'stream' => function ($data) use ($connection, &$reply) {
                $delta = $data['choices'][0]['delta']['content'] ?? '';
                if ($delta) {
                    $reply .= $delta;
                    $connection->send(new Chunk("data: " . $delta . "\n\n"));
                }
            },
            'complete' => function ($result, $response) use ($connection, $request, $sid, $roleType, &$reply,$user_id) {
                $connection->send(new Chunk("data: [DONE]\n\n"));
                $connection->send(new Chunk(''));

                if (!empty(trim($reply))) {
                    $sessionKey = 'chat_history_' . $roleType;
                    $history = $request->session()->get($sessionKey) ?? [];
                    $history[] = ['role' => 'assistant', 'content' => $reply];
                    $request->session()->set($sessionKey, $history);

                    Db::name('wa_chat_logs')->save([
                        'session_id' => $sid,
                        'role' => 'assistant',
                        'role_type' => $roleType,
                        'content' => $reply,
                        'user_id' => $user_id
                    ]);

                    file_put_contents(runtime_path() . '/reply.log', "REPLY:\n" . $reply . "\n", FILE_APPEND);
                }
            }
        ]);

        return response('', 200)->withHeaders([
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            "Transfer-Encoding" => "chunked",
            'Connection' => 'keep-alive',
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