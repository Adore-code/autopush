<?php

namespace app\controller;

use Noweh\TwitterApi\Client;
use support\Response;

class TwitterController
{
    /**
     * 配置 Twitter API 客户端
     *
     * @return Client
     */
    private function getClient(): Client
    {
        $settings = [
            'account_id' => 'YOUR_ACCOUNT_ID',
            'access_token' => 'YOUR_ACCESS_TOKEN',
            'access_token_secret' => 'YOUR_ACCESS_TOKEN_SECRET',
            'consumer_key' => 'YOUR_CONSUMER_KEY',
            'consumer_secret' => 'YOUR_CONSUMER_SECRET',
            'bearer_token' => 'YOUR_BEARER_TOKEN',
        ];

        return new Client($settings);
    }

    /**
     * 发送推文
     *
     * @param string $text 推文内容
     * @return Response
     */
    public function createTweet(string $text): Response
    {
        $client = $this->getClient();

        $response = $client->tweet()->create()->performRequest([
            'text' => $text
        ]);

        return json($response);
    }

    /**
     * 转发推文
     *
     * @param string $tweetId 要转发的推文ID
     * @return Response
     */
    public function retweet(string $tweetId): Response
    {
        $client = $this->getClient();

        $response = $client->tweet()->retweet(['tweet_id' => $tweetId])->performRequest();

        return json($response);
    }

    /**
     * 搜索特定推文
     *
     * @param string $query 搜索关键词
     * @param int $maxResults 最大返回结果数（默认10）
     * @return Response
     */
    public function searchTweets(string $query, int $maxResults = 10): Response
    {
        $client = $this->getClient();

        $response = $client->tweetSearch()
            ->addFilterOnKeywordOrPhrase([$query])
            ->setMaxResults($maxResults)
            ->performRequest();

        return json($response);
    }

    /**
     * 获取用户的最新推文
     *
     * @param string $userId 用户ID
     * @param int $maxResults 最大返回结果数（默认5）
     * @return Response
     */
    public function getUserRecentTweets(string $userId, int $maxResults = 5): Response
    {
        $client = $this->getClient();

        $response = $client->users()
            ->find($userId)
            ->tweets()
            ->setMaxResults($maxResults)
            ->performRequest();

        return json($response);
    }
}