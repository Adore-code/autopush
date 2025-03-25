<?php

namespace plugin\signin\app\controller;

use plugin\signin\app\service\Config;
use plugin\user\app\service\Register;
use support\Response;

class RegisterController extends Base
{
    /**
     * 不需要登录的方法
     * @var string[]
     */
    protected $noNeedLogin = ['index',];
    
    public function index(): Response
    {
        $register = Register::getSetting();

        if (empty($register['register_enable'])) {
            return view('register-close');
        }

        $beian = Config::get('setting.beian', '');
        $matches = [];
        if (!empty($beian)){
            preg_match('/\d+/', $beian, $matches);
        }
        return view('register', [
            'settings' => $register,
            'show_list'  => Config::get('register.show_list',[]),
            'main_title' => Config::get('register.main_title',''),
            'subtitle'   => Config::get('register.subtitle',''),
            'logo_text'  => Config::get('register.logo_text',''),

            'footer_html' => Config::get('setting.footer_html',''),
            'icp' => Config::get('setting.icp',''),
            'beian' => $beian,
            'beian_number' => $matches[0] ?? '',
        ]);
    }
}