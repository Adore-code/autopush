<?php

namespace plugin\signin\app\service\CaptchaCode;

interface Captcha
{
    public function __construct($config, array $extra = []);

    /**
     * @param $ticket
     * @return boolean
     * @throws \Exception
     */
    public function check($ticket): bool;
}