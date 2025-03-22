<?php

namespace plugin\signin\app\service;

use plugin\user\api\Captcha;

class Sms
{

    /**
     * @throws \Exception
     */
    public static function send($mobile, $key): void
    {
        try {
            $captchaData = Captcha::create($mobile, rand(1000, 9999));
        } catch (\Exception $e) {
            throw new \Exception('验证码生成失败');
        }

        request()->session()->set($key, $captchaData);


        try {
            \plugin\sms\api\Sms::sendByTag($mobile, 'captcha', ['code' => $captchaData['code'],]);
        } catch (\Throwable $e) {
            throw new \Exception('验证码发送失败');
        }

    }
}