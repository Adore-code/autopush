<?php

namespace plugin\signin\app\service\CaptchaCode;

class ImageCaptcha implements Captcha
{
    protected $config = [];
    protected $extra = [];
    public function __construct($config, array $extra = [])
    {
        $this->config = is_array($config) ? $config : [];
        $this->extra = $extra;
    }

    public function check($ticket): bool
    {
        if (empty($ticket)){
            throw new \Exception('验证码不正确');
        }

        $check_code = $this->extra['check_code'] ?? '';

        if (empty($check_code)){
            throw new \Exception('验证码不正确');
        }

        $data = session()->get($check_code);


        if (empty($data) || !isset($data['code']) || $data['code'] != $ticket){
            session()->delete($check_code);
            throw new \Exception('验证码不正确');
        }

        return true;
    }

}