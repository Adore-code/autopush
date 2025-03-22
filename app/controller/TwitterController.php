<?php

namespace app\controller;

use GuzzleHttp\Exception\GuzzleException;
use Noweh\TwitterApi\Client;
use support\Response;

class TwitterController
{
    /**
     * 配置 Twitter API 客户端
     *
     * @return Client
     */
    private function getClient($settings): Client
    {
        return new Client($settings);
    }

    /**
     * 发送推文
     *
     * @param $settings
     * @param string $text 推文内容
     * @return mixed
     */
    public function createTweet($settings, string $text): mixed
    {
        try {
            $client = $this->getClient($settings);

            $response = $client->tweet()->create()->performRequest([
                'text' => $text
            ]);

            return $response;
        } catch (\Throwable $e) {
            return json_decode($e->getMessage());
        }
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