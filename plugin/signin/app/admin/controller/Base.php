<?php

namespace plugin\signin\app\admin\controller;

use support\Response;

class Base
{

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