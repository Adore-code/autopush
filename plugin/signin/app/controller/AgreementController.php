<?php

namespace plugin\signin\app\controller;

use plugin\signin\app\service\Config;
use support\Response;

class AgreementController extends Base
{
    /**
     * 不需要登录的方法
     * @var string[]
     */
    protected $noNeedLogin = ['index',];

    public function index(): Response
    {

        return view('agreement', [
            'header' => Config::get('agreement.header'),
            'body' => Config::get('agreement.body'),
            'footer' => Config::get('agreement.footer'),
        ]);
    }
}
