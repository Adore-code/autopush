<?php

namespace app\controller;

use support\Request;
use support\think\Db;

class BinanceController extends CronController
{
    public string $binanceLink = 'https://www.binance.com/bapi/composite/v4/friendly/pgc/feed/news/list?pageIndex=1&pageSize=20&strategy=6&tagId=0&featured=false';

    public function saveData(): \support\Response
    {
        $binanceNews = $this->getBinanceNews();
        $binanceNews = $this->formatData('binance', $binanceNews);
        $result = $this->save($binanceNews);

        return json($result);
    }

    protected function getBinanceNews()
    {
        $url = $this->binanceLink;
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate'); // 让 cURL 自动解压 Gzip
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'clienttype: web',
            'bnc-time-zone: Asia/Shanghai',
            'lang: zh-CN',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
            'Connection: keep-alive'
        ]);

        $output = curl_exec($ch);
        curl_close($ch);

        $newsList = json_decode($output, true);
        if(isset($newsList['data']['vos'])) return $newsList['data']['vos'];

        return false;
    }
}
