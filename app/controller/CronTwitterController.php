<?php

namespace app\controller;

use plugin\admin\app\model\TAccount;
use support\think\Db;
use think\db\exception\DbException;

class CronTwitterController
{
    public function createArticle()
    {
        $twitter = new TwitterController();

        $articles = Db::name('wa_article')
            ->alias('a')
            ->join('wa_account t', 'a.x_account = t.x_account')
            ->where('a.status', '=', 0)
            ->field('a.*, t.x_account, t.x_access_token, t.x_access_token_secret, t.x_consumer_key, t.x_consumer_secret, t.x_bearer_token') // 按需选字段
            ->select()
            ->toArray();

        foreach ($articles as $account) {
            $publicAt = strtotime($account['public_at']);
            if($publicAt > time()) return;

            $settings = [
                'account_id' => $account['x_account'],
                'access_token' => $account['x_access_token'],
                'access_token_secret' => $account['x_access_token_secret'],
                'consumer_key' => $account['x_consumer_key'],
                'consumer_secret' => $account['x_consumer_secret'],
                'bearer_token' => $account['x_bearer_token'],
            ];

            $content = strip_tags($account['ai_content']);
            $response = [];
            if ($content) $response = $twitter->createTweet($settings, $content);
            if ($response) {
                $status = 1;
                $error_info = '';
                $article_id = 0;
                if (isset($response->status))
                {
                    $status     = 2;
                    $error_info = $response->detail;
                }
                if (isset($response->data->id)) $article_id = $response->data->id;

                $data = [
                    'status' => $status,
                    'error_info' => $error_info,
                    'article_id' => $article_id,
                    'updated_at' => date('Y-m-d H:i:s', time()),
                    'public_at' => date('Y-m-d H:i:s', time()),
                ];

                try {
                    Db::name('wa_article')->where('id', '=', $account['id'])->data($data)->update();
                } catch (DbException $e) {
                    print_r($e->getMessage());
                    print_r($e->getTraceAsString());
                }
            }
        }
    }
}
