<?php

namespace plugin\signin\app\controller;

use plugin\user\api\FormException;
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
        return \response('404', 404);
        $register = Register::getSetting();

        if (empty($register['register_enable'])) {
            return view('register-close');
        }

        $beian = $this->config->getPluginLoginConfig('setting','beian', '');
        $matches = [];
        if (!empty($beian)){
            preg_match('/\d+/', $beian, $matches);
        }
        return view('register', [
            'settings' => $register,
            'show_list' => $this->config->getPluginLoginConfig('register','show_list',''),
            'main_title' => $this->config->getPluginLoginConfig('register','main_title',''),
            'subtitle' => $this->config->getPluginLoginConfig('register','subtitle',''),
            'logo_text' => $this->config->getPluginLoginConfig('register','logo_text',''),

            'footer_html' => $this->config->getPluginLoginConfig('setting','footer_html',''),
            'icp' => $this->config->getPluginLoginConfig('setting','icp',''),
            'beian' => $beian,
            'beian_number' => $matches[0] ?? '',
        ]);

    }
}