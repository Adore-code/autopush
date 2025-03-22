<?php

namespace plugin\signin\app\controller;

use Exception;
use plugin\user\api\Captcha;
use support\Request;
use support\Response;
use Webman\Captcha\CaptchaBuilder;

class CaptchaController extends Base
{
    /**
     * 不需要登录的方法
     * @var string[]
     */
    protected $noNeedLogin = ['image', 'email', 'mobile'];


    /**
     * 图片验证码
     * @param Request $request
     * @param string $type
     * @return Response
     * @throws Exception
     */
    public function image(Request $request, string $type = 'captcha'): Response
    {
        $type = $request->get('type', 'captcha');
        $builder = new CaptchaBuilder();
        $captchaData = Captcha::create();
        $builder->setPhrase($captchaData['code']);
        $builder->build();
        $request->session()->set("check-mobile", $captchaData);
        $img_content = $builder->get();
        return response($img_content, 200, ['Content-Type' => 'image/jpeg']);
    }

}