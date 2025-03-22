<?php

namespace plugin\signin\app\service;

use plugin\admin\app\model\Option;

class CaptchaCode
{

    protected static $instanceClass = [
        'ImageCaptcha' => \plugin\signin\app\service\CaptchaCode\ImageCaptcha::class,
        'TxCaptcha' => \plugin\signin\app\service\CaptchaCode\TxCaptcha::class,
        'NoCaptcha' => \plugin\signin\app\service\CaptchaCode\NoCaptcha::class,
    ];

    /**
     * @throws \Exception
     */
    public static function verify(string $key, string $ticket, array $extra = []): bool
    {
        $config = Config::get('captcha.setting');

        if (!isset($config[$key])){
            return true;
        }

        $config = $config[$key];

        //如果没有配置类型则直接返回成功
        if (empty($config['type'])){
            return true;
        }

        $type = $config['type'];

        /** @var CaptchaCode\Captcha $instance */
        $instance = new self::$instanceClass[$type]($config['param'], $extra);

        return $instance->check($ticket);
    }
}
