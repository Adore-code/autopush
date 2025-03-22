<?php

namespace plugin\signin\app\controller;

use plugin\signin\app\service\Config;
use support\Response;

class Base
{

    protected Config $config;
    public function __construct()
    {
        $this->config = new Config();
    }

    /**
     * 成功
     * @param string $msg
     * @param array $data
     * @return Response
     */
    public function success(string $msg = '', array $data = []): Response
    {
        return json(['code' => 0, 'msg' => $msg, 'data' => $data]);
    }

    /**
     * 失败
     * @param string $msg
     * @param array $data
     * @return Response
     */
    public function error(string $msg = '', array $data = []): Response
    {
        return json(['code' => 1, 'msg' => $msg, 'data' => $data]);
    }

}