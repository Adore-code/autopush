<?php

namespace app\controller;

use GuzzleHttp\Exception\RequestException;
use support\think\Db;
use think\db\exception\DbException;
use GuzzleHttp\Client;
use League\OAuth1\Client\Credentials\ClientCredentials;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth1\Client\Server\Twitter as OAuthTwitter;

class CronTwitterController
{
    /**
     * 上传本地图片，返回 media_id_string
     * @return string
     * @throws GuzzleException
     */
    public function uploadMedia($user, $imagePath): string
    {
        $consumer_key = $user['x_consumer_key'];
        $consumer_secret = $user['x_consumer_secret'];
        $oauth_token = $user['x_access_token'];
        $oauth_token_secret = $user['x_access_token_secret'];

        if (!file_exists($imagePath)) {
            exit("❌ 文件不存在: $imagePath\n");
        }

        // 使用 league/oauth1-client 构建 OAuth 签名
        $server = new OAuthTwitter([
            'identifier' => $consumer_key,
            'secret' => $consumer_secret,
            'callback_uri' => 'oob',
        ]);

        $tempCredentials = new ClientCredentials();
        $tempCredentials->setIdentifier($oauth_token);
        $tempCredentials->setSecret($oauth_token_secret);

        $uri = 'https://upload.twitter.com/1.1/media/upload.json';
        $method = 'POST';

        // 构建 OAuth 头部
        $headers = $server->getHeaders($tempCredentials, $method, $uri, []);

        // 创建 Guzzle 客户端
        $client = new Client([
            'proxy' => [
                'http'  => 'socks5h://customer-Yg6CbpNhB1:PuGTjkGm03IVobz@gate-sg.ipfoxy.io:58688',
                'https' => 'socks5h://customer-Yg6CbpNhB1:PuGTjkGm03IVobz@gate-sg.ipfoxy.io:58688',
            ]
        ]);

        try {
            $response = $client->post($uri, [
                'headers' => [
                    'Authorization' => $headers['Authorization'],
                ],
                'multipart' => [
                    [
                        'name' => 'media',
                        'contents' => fopen($imagePath, 'r')
                    ]
                ],
            ]);

            $body = json_decode($response->getBody(), true);

            if (isset($body['media_id_string'])) {
                return $body['media_id_string'];
            } else {
                return false;
            }
        } catch (RequestException $e) {
            return false;
        }
    }

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
            if ($publicAt > time()) return;

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
            if ($content) $response = $twitter->createTweet($settings, $content, $account['img_id']);
            if ($response) {
                $status = 1;
                $error_info = '';
                $article_id = 0;
                if (isset($response->status)) {
                    $status = 2;
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
