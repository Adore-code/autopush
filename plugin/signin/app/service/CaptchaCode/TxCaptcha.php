<?php

namespace plugin\signin\app\service\CaptchaCode;

use TencentCloud\Captcha\V20190722\CaptchaClient;
use TencentCloud\Captcha\V20190722\Models\DescribeCaptchaResultRequest;
use TencentCloud\Captcha\V20190722\Models\DescribeCaptchaResultResponse;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;

class TxCaptcha implements Captcha
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
        if (empty($ticket)){
            throw new \Exception('验证码不正确');
        }

        $ticket = json_decode($ticket, true);
        $resp = self::checkCaptcha(
            $this->config['secret_id'],
            $this->config['secret_key'],
            (int)$this->config['captcha_app_id'],
            $this->config['app_secret_key'],
            $ticket['ticket'],$ticket['randstr'],
            request()->getRealIp()
        );

        if ($resp->getCaptchaCode() != 1){
            throw new \Exception('验证码不正确');
        }

        if ($resp->getEvilLevel() !== 0){
            throw new \Exception('验证码不正确');
        }

        return true;
    }

    /**
     * @param $secret_id
     * @param $secret_key
     * @param $captcha_app_id
     * @param $app_secret_key
     * @param $ticket
     * @param $rand_str
     * @param $user_ip
     * @return DescribeCaptchaResultResponse
     */
    public static function checkCaptcha($secret_id, $secret_key,$captcha_app_id, $app_secret_key, $ticket, $rand_str, $user_ip): DescribeCaptchaResultResponse
    {
        $cred = new Credential($secret_id, $secret_key);
        // 实例化一个http选项，可选的，没有特殊需求可以跳过
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint("captcha.tencentcloudapi.com");

        // 实例化一个client选项，可选的，没有特殊需求可以跳过
        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        // 实例化要请求产品的client对象,clientProfile是可选的
        $client = new CaptchaClient($cred, "", $clientProfile);

        // 实例化一个请求对象,每个接口都会对应一个request对象
        $req = new DescribeCaptchaResultRequest();

        $params = [
            "CaptchaType" => 9,
            "Ticket" => $ticket,
            "UserIp" => $user_ip,
            "BusinessId" => null,
            "SceneId" => null,
            "MacAddress" => null,
            "Imei" => null,
            "Randstr" => $rand_str,
            "CaptchaAppId" => $captcha_app_id,
            "AppSecretKey" => $app_secret_key,
            "NeedGetCaptchaTime" => 1,
        ];
        $req->fromJsonString(json_encode($params));

        // 返回的resp是一个DescribeCaptchaResultResponse的实例，与请求对象对应
        return $client->DescribeCaptchaResult($req);
    }
}