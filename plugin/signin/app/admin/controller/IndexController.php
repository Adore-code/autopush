<?php

namespace plugin\signin\app\admin\controller;

use plugin\admin\app\model\Option;
use plugin\signin\app\service\Config;

class IndexController
{
    public function index()
    {

        return view('setting', [
            'setting' => Config::get('setting',['main_title' => '', 'subtitle' => '', 'logo_text' => '', 'show_list' => [], 'icp' => '', 'beian' =>  '', 'footer_html' => '',]),
            'captcha' => [
                'captchaType' => Config::get('captcha.captchaType', ''),
                'setting' => Config::get('captcha.setting',['account_login' => [], 'account_check' => [], 'sms_login' => [],]),
            ],
            'login' => Config::get('login', ['main_title' => '', 'subtitle' => '', 'logo_text' => '', 'show_list' => [],]),
            'register' => Config::get('register',  ['main_title' => '', 'subtitle' => '', 'logo_text' => '', 'show_list' => [],]),
            'agreement' => [
                'header' => str_replace("\n",'\n', Config::get('agreement.header')),
                'body' => str_replace("\n",'\n', Config::get('agreement.body')),
                'footer' => str_replace("\n",'\n', Config::get('agreement.footer')),
            ]
        ]);
    }
}
