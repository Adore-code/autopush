<?php

namespace app\controller;

use plugin\admin\app\model\Reply;
use GuzzleHttp\Client;
use support\think\Db;
use think\db\exception\DbException;
use function Symfony\Component\Clock\now;

class ChatController
{
    public function getWaitReply(): array
    {
        return Db::name('wa_reply')->where('status', '=', '0')->column('x_account', 'x_account');
    }

    public function getAllOpenTasks()
    {
        // 获取所有任务
        $tasks = Db::name('wa_task')
            ->alias('t')
            ->join('wa_account a', 't.x_account = a.x_account')
            ->where(['t.status' => 1])
            ->field('t.*, a.*, a.status as account_status') // 按需选字段
            ->select()
            ->toArray();

        $accounts = [];
        $accountIds = [];
        foreach ($tasks as $task) {
            $accounts[$task['x_account']] = $task;
            $accountIds[] = $task['x_account'];
        }

        //获取最后一条推文
        $subQuery = Db::name('wa_article')
            ->field('*, MAX(id) as max_id') // 或者 MAX(created_at)
            ->where('x_account', 'in', $accountIds)
            ->group('x_account')
            ->buildSql();

        $lastArticle = Db::table($subQuery . ' sub')
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

            $publicTime  =  strtotime($lastArticle['public_at']);
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

            $content = "新闻内容如下，从中选取一条或几条符合角色描述的新闻组成推文：\n";
            foreach ($news as $key => $new) {
                $content .= $key + 1 . " 标题: {$new['title']},  内容：{$new['content']} \n";
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
            ->field('r.*, t.x_ai_template') // 按需选字段
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

    protected function completions($message)
    {
        if(empty($message['x_ai_template']) || empty($message['source_content'])) return '';

        $apiKey = getenv('OPENAI_API_KEY');
        $apiUrl = 'https://api.openai.com/v1/chat/completions';

        $postData = [
            'model' => 'gpt-4-turbo',
            'messages' => [
                ['role' => 'system', 'content' => $message['x_ai_template']],
                ['role' => 'user',   'content' => $message['source_content']]
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
                'timeout' => 10
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
