<?php

namespace plugin\autopush\app\controller;

use GuzzleHttp\Exception\RequestException;
use support\Request;
use support\Response;
use support\think\Db;
use Workerman\Http\Client;

// 推荐用 UUID 做 session_id（也可以用自定义）

class AIController
{
    private string|array|false $apiKey;

    public function __construct()
    {
        $this->apiKey = getenv('OPENAI_API_KEY');
    }

    public function getWaitDrawArticles()
    {
        $articles = Db::name('wa_article_copy1')
            ->alias('a')
            ->join('wa_task t', 'a.x_account = t.x_account')
            ->where('a.status = 0')
            ->where('a.img', 'null')
            ->where('t.x_image', '=', 1)
            ->field('a.*, t.x_image')
            ->select()
            ->toArray();

        foreach($articles as $article)
        {
            $content = $article['source_content'];
            $img = $this->draw($content);
            return $img;
        }

        return '';
    }

    public function draw(string $prompt = '一棵大树'): string
    {
        if (empty($prompt)) {
            return json_encode(['error' => 'Prompt cannot be empty']);
        }

        $apiKey = $this->apiKey;
        $endpoint = 'https://api.openai.com/v1/images/generations';

        $data = [
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
            'response_format' => 'b64_json'
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: ' . 'Bearer ' . $apiKey
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            error_log('Draw Error: ' . curl_error($ch));
            return json_encode(['error' => curl_error($ch)]);
        }

        $result = json_decode($response, true);
        curl_close($ch);

        if (isset($result['data'][0]['b64_json'])) {
            $base64Image = $result['data'][0]['b64_json'];
            return '<img src="data:image/png;base64,' . $base64Image . '" />';
        } elseif (isset($result['error'])) {
            return json_encode(['error' => $result['error']['message'] ?? 'Unknown error']);
        }

        return json_encode(['error' => 'Unexpected API response', 'raw' => $response]);
    }

    public function chat(Request $request): Response
    {
        if(isExpired()) return view('/plugin/autopush/public/demos/expired');

        if ($request->isPost()) {
            $post = json_decode($request->rawBody(), true);
            $message = $post['message'] ?? '';
            $roleType = $post['role'] ?? 'default';
            $apiKey = getenv('OPENAI_API_KEY');

            // 初始化对话唯一 session_id
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
                'content' => $message
            ]);

            // 调用 OpenAI 接口
            $response = json_decode(file_get_contents('https://api.openai.com/v1/chat/completions', false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n" .
                        "Authorization: Bearer $apiKey\r\n",
                    'content' => json_encode([
                        'model' => 'gpt-4-turbo',
                        'messages' => $history
                    ])
                ]
            ])), true);

            $reply = $response['choices'][0]['message']['content'] ?? '请求失败';
            $usage = $response['usage'] ?? [];

            $history[] = ['role' => 'assistant', 'content' => $reply];
            session()->set($sessionKey, $history);

            // 保存助手回复
            Db::name('wa_chat_logs')->save([
                'session_id' => $sid,
                'role' => 'assistant',
                'role_type' => $roleType,
                'content' => $reply
            ]);

            return json([
                'reply' => $reply,
                'usage' => $usage
            ]);
        }
        return view('ai/chat', [
            'title' => 'AI门通行证',
        ]);
    }

    public function clear(Request $request): Response
    {
        $post = json_decode($request->rawBody(), true);
        $roleType = $post['role'] ?? 'default';
        session()->delete('chat_history_' . $roleType);
        return response('ok');
    }
}