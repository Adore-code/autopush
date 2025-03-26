<?php

namespace app\controller;

use support\Request;
use support\Response;
use support\think\Db;

class CronController
{
    public function save($data): Response
    {
        $result = Db::table('wa_news')->replace()->insertAll($data);
        return json($result);
    }

    public function getSourceIdList($source)
    {
        return Db::table('wa_news')->where('source', '=', $source)->column('new_id');
    }

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
            $result[$k]['content']    = strip_tags($v[$config['content']]);
            $result[$k]['link']       = $v[$config['link']];
            $result[$k]['public_at']  = $v[$config['public_at']];
            $result[$k]['source']     = $source;
            $result[$k]['created_at'] = time();
        }

        return $result;
    }
}
