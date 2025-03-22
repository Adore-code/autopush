<?php

namespace plugin\signin\app\service\CaptchaCode;

class NoCaptcha implements Captcha
{

    protected array $config;
    protected array $extra;
    public function __construct($config, array $extra = [])
    {
        $this->config = is_array($config) ? $config : [];
        $this->extra = $extra;
    }


    public function check($ticket): bool
    {
        return true;
    }
}