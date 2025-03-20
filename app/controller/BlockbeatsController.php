<?php

namespace app\controller;

use support\Request;
use support\think\Db;

class BlockbeatsController extends CronController
{
    public string $blockbeatsLink = 'https://api.blockbeats.cn/v2/newsflash/list?page=1&limit=20&ios=-2&end_time=&detective=-2';

    public function saveData(): \support\Response
    {
        $news = $this->getBlockbeatsNews();
        $news = $this->formatData('blockbeats', $news);
        $result = $this->save($news);

        return json($result);
    }

    protected function getBlockbeatsNews()
    {
        $url = $this->blockbeatsLink;
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate'); // 让 cURL 自动解压 Gzip
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
        ]);

        $output = curl_exec($ch);
        curl_close($ch);

        $newsList = json_decode($output, true);
        if(isset($newsList['data']['list'])) return $newsList['data']['list'];

        return false;
    }
}
