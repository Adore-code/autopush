<?php

namespace app\controller;

use support\Request;
use support\Response;
use support\think\Db;

class OdailyController extends CronController
{
    public string $odailyLink = 'https://www.odaily.news/api/pp/api/info-flow/newsflash_columns/newsflashes?b_id=&per_page=20&is_import=false';

    /**
     * 保存到数据库
     * @return Response
     */
    public function saveData(): Response
    {
        $odailyNews = $this->getOdailyNews();
        $odailyNews = $this->formatData('odaily', $odailyNews);
        $result = $this->save($odailyNews);

        return json($result);
    }

    /**
     * 格式化数据
     * @param $source
     * @param $data
     * @return array
     */
    protected function formatData($source, $data): array
    {
        $result = [];
        $config = config("cron.$source");
        $idList = $this->getSourceIdList($source);

        foreach($data as $k => $v) {
            if(in_array($v[$config['new_id']], $idList)) continue;

            $result[$k]['new_id']     = $v[$config['new_id']];
            $result[$k]['title']      = $v[$config['title']];
            $result[$k]['sub_title']  = $v[$config['sub_title']];
            $result[$k]['content']    = $v[$config['content']];
            $result[$k]['link']       = $v['share_data']['default']['url'];
            $result[$k]['public_at']  = strtotime($v['published_at']);
            $result[$k]['img_url']    = $v['share_data']['default']['cover'];
            $result[$k]['source']     = $source;
            $result[$k]['created_at'] = time();
        }

        return $result;
    }

    /**
     * 从接口获取数据
     * @return false|mixed
     */
    protected function getOdailyNews(): mixed
    {
        $url = $this->odailyLink;
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
        if(isset($newsList['data']['items'])) return $newsList['data']['items'];

        return false;
    }
}
