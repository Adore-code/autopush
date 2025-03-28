<?php

namespace app\controller;

use plugin\admin\app\model\Reply;
use GuzzleHttp\Client;
use support\think\Db;
use think\db\exception\DbException;
use function Symfony\Component\Clock\now;

class ChatController
{
    private string|array|false $apiKey;
    private string $apiUrl;

    public function __construct()
    {
//        $this->apiKey = getenv('OPENAI_API_KEY');
//        $this->apiUrl = 'https://api.openai.com/v1/chat/completions';
        $this->apiKey = getenv('BAILIAN_API_KEY');
        $this->apiUrl = 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions';
    }

    public function getWaitReply(): array
    {
        return Db::name('wa_reply')->where('status', '=', '0')->column('x_account', 'x_account');
    }

    public function getAllOpenTasks()
    {
        $expiredAccounts = Db::name('wa_admins')->where('expired_at', '<', time())->column('id', 'id');

        // 获取所有任务
        $tasks = Db::name('wa_task')
            ->alias('t')
            ->join('wa_account a', 't.x_account = a.x_account')
            ->where(['t.status' => 1])
            ->field('t.*, a.*, a.status as account_status') // 按需选字段
            ->select()
            ->toArray();

        $accounts   = [];
        $accountIds = [];
        foreach ($tasks as $task) {
            if(in_array($task['user_id'], $expiredAccounts)) continue;

            $accounts[$task['x_account']] = $task;
            $accountIds[] = $task['x_account'];
        }

        // 子查询：每个账号的最大发布时间
        $subQuery = Db::name('wa_article')
            ->field('x_account, MAX(public_at) as max_public')
            ->where('x_account', 'in', $accountIds)
            ->group('x_account')
            ->buildSql();

        // 主查询：拿到完整记录
        $lastArticle = Db::name('wa_article')
            ->alias('a')
            ->join([$subQuery => 'b'], 'a.x_account = b.x_account AND a.public_at = b.max_public')
            ->order('a.public_at', 'desc')
            ->select()
            ->toArray();

        // 按 x_account 为索引重建数组
        $lastArticles = array_column($lastArticle, null, 'x_account');

        //获取等待AI回复的推文
        $replys = $this->getWaitReply();

        $waitAccounts = [];
        //获取等待发布的账号
        foreach ($accounts as $xAccount => $account) {
            if(isset($replys[$xAccount])) continue;
            if(!isset($lastArticles[$xAccount]))
            {
                $waitAccounts[$xAccount] = $account;
                continue;
            }
            $lastArticle = $lastArticles[$xAccount];

            if($lastArticle['status'] == 0) continue;

            $publicTime  = strtotime($lastArticle['public_at']);
            $now         = time();

            $push_interval = $account['push_interval'];
            $diffInMinutes = intval(($now - $publicTime) / 60);

            if($diffInMinutes > $push_interval || $push_interval - $diffInMinutes < 10){
                $waitAccounts[$xAccount] = $account;
            }
        }

        $news = $this->getNews();
        foreach ($waitAccounts as $xAccount => $account) {
            $news = $this->selectNews($news, $account['source']);

            if(empty($news)) continue;

            $content = '';
            foreach ($news as $new) {
                $content .= " {$new['content']} \n\n";
            }

            $data = [
                'x_account' => $xAccount,
                'source_content' => $content,
                'status' => 0,
                'created_at' => now(),
                'public_at' => date('Y-m-d H:i:s',time() + rand(600, 1200)),
            ];

            Reply::insert($data);
        }

        return $waitAccounts;
    }

    public function selectNews($newsList, $source = [], int $limit = 5): array
    {
        // 统一处理成数组形式
        if (is_string($source)) {
            $source = [$source];
        }

        $selected = [];

        foreach ($source as $src) {
            if (!empty($newsList[$src])) {
                $selected = array_merge($selected, $newsList[$src]);
            }
        }

        // 合并所有来源的新闻
        $allNews = array_merge(...array_values($newsList));

        // 随机抽取指定数量
        shuffle($selected);
        return array_slice($allNews, 0, $limit);
    }

    public function getNews()
    {
        $news['binance']    = Db::name('wa_news')->where('source', '=', 'binance')->limit(5)->order('id','desc')->select()->toArray();
        $news['blockbeats'] = Db::name('wa_news')->where('source', '=', 'blockbeats')->limit(5)->order('id','desc')->select()->toArray();
        $news['odaily']     = Db::name('wa_news')->where('source', '=', 'odaily')->limit(5)->order('id','desc')->select()->toArray();
        return $news;
    }

    public  function getAiReply()
    {
        $waits = Db::name('wa_reply')
            ->alias('r')
            ->join('wa_task t', 'r.x_account = t.x_account')
            ->where(['r.status' => 0])
            ->field('r.*, t.x_ai_template, t.x_limit') // 按需选字段
            ->select()
            ->toArray();

        foreach ($waits as $wait) {
            $status  = 0;
            $aiReply = $this->completions($wait);

            if($aiReply['code'] == 200) $status = 1;

            Db::startTrans();
            try {
                $data = [
                    'ai_content' => $aiReply['reply'],
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s', time()),
                ];

                Db::name('wa_reply')->where('id', '=', $wait['id'])->data($data)->update();
                if($status ==1) {
                    $data = [
                        'x_account' => $wait['x_account'],
                        'source_content' => $wait['source_content'],
                        'ai_content' => $aiReply['reply'],
                        'status' => 0,
                        'created_at' => date('Y-m-d H:i:s', time()),
                        'public_at' => $wait['public_at'],
                    ];
                    Db::name('wa_article')->insert($data);
                }
                Db::commit();
            } catch (DbException $e) {
                Db::rollback();
            }
        }
    }

    public function reply($message)
    {
        if(empty($message['ai_template']) || empty($message['content'])) return '';

        $apiKey = $this->apiKey;
        $apiUrl = $this->apiUrl;

        $content  = strip_tags($message['content']);

        $postData = [
//            'model' => 'gpt-4-turbo',
            'model' => 'deepseek-v3',

            'messages' => [
                ['role' => 'system', 'content' => $message['ai_template']],
                ['role' => 'user',   'content' => $content]
            ],
            'stream' => false
        ];
        $client = new Client();

        try {
            $response = $client->post($apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json'
                ],
                'body' => json_encode($postData),
                'timeout' => 30
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $content = $data['choices'][0]['message']['content'] ?? '';

            return ['code' => 200, 'reply' => $content];
        } catch (\Throwable $e) {
            return [
                'code' => 500,
                'reply' => $e->getMessage(),
            ];
        }
    }

    protected function completions($message)
    {
        if(empty($message['x_ai_template']) || empty($message['source_content'])) return '';

        $apiKey = $this->apiKey;
        $apiUrl = $this->apiUrl;

        $x_ai_template = $message['x_ai_template'];
        $content = "\n\n总结以下新闻生成一条符合当前角色描述的有价值有卖点的推文：\n";
        $content .= $message['source_content'];

        if(isset($message['x_limit']) && $message['x_limit'] == 1) $x_ai_template .= '字数一定要控制在80~100字，不要超过推特字数限制';

//        $postData = [
//            'model' => 'gpt-4-turbo',
//            'messages' => [
//                ['role' => 'system', 'content' => $x_ai_template],
//                ['role' => 'user',   'content' => $content]
//            ],
//            'stream' => false
//        ];

        $postData = [
            'model' => 'deepseek-v3',
            'messages' => [
                ['role' => 'system', 'content' => $x_ai_template],
                ['role' => 'user',   'content' => $content]
            ],
            'stream' => false
        ];

        $client = new Client();

        try {
            $response = $client->post($apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json'
                ],
                'body' => json_encode($postData),
                'timeout' => 30
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            $content = $data['choices'][0]['message']['content'] ?? '';

            return ['code' => 200, 'reply' => $content];
        } catch (\Throwable $e) {
            return [
                'code' => 500,
                'reply' => $e->getMessage(),
            ];
        }
    }
}
