<?php

namespace plugin\signin\app\controller;

use plugin\admin\app\model\Option;
use plugin\signin\app\service\Auth;
use plugin\signin\app\service\CaptchaCode;
use plugin\signin\app\service\Config;
use plugin\user\api\Captcha;
use plugin\user\api\Limit;
use plugin\user\app\model\User;
use support\exception\BusinessException;
use support\Redis;
use support\Request;
use support\Response;
use Webman\Event\Event;


class LoginController extends Base
{
    /**
     * 不需要登录的方法
     * @var string[]
     */
    protected $noNeedLogin = [ 'index', 'account', 'checkAccount', 'smsLogin' ];

    private $CAPTCHA_MOBILE_KEY = 'captcha-sms-check-mobile';

    public function index(): \support\Response
    {
//        return json(Config::get('login.show_list', []));
        $loginUserId = session('user.id') ?? session('user.uid');
        $loginUserUuid = session('user.uuid') ?? null;

        if (!empty($loginUserId) && !empty($loginUserUuid)) {
           if (!Auth::checkRedis($loginUserId, $loginUserUuid)){
               return redirect('/');
           }
        }

        $beian = Config::get('setting.beian', '');
        $matches = [];
        if (!empty($beian)){
            preg_match('/\d+/', $beian, $matches);
        }

        $captchaTypeList = Config::get('captcha.captchaType', []);
        $publicAccess = [];

        foreach ($captchaTypeList as $type => $value) {
            if (empty($value['param'])){
                continue;
            }

            foreach ($value['param'] as $key => $item) {
                if (empty($item['access'])) continue;
                if (in_array('public',$item['access'])){
                    $publicAccess[$type][] = $key;
                }
            }
        }


        // 获取验证码系统配置
        $captcha = [
            'account_login' => Config::get('captcha.setting.account_login', []),
            'account_check' => Config::get('captcha.setting.account_check', []),
            'sms_login' => Config::get('captcha.setting.sms_login', []),
        ];

        foreach ($captcha as $key => $value) {
            $param = $value['param'] ?? [];
            foreach ($param as $arg => $item) {
                if(!in_array($arg, $publicAccess[$value['type']] ?? [])){
                    unset($captcha[$key]['param'][$arg]);
                }
            }
        }

        $backgroundImage = rand(1, 3);


        return view('login', [
            'backgroundImage' => "/app/signin/image/bg.jpg",
            'show_list' => Config::get('login.show_list', []),
            'main_title' => Config::get('login.main_title', ''),
            'subtitle' =>  Config::get('login.subtitle', ''),
            'logo_text' => Config::get('login.logo_text', ''),
            'icp' => Config::get('setting.icp', ''),
            'footer_html' => Config::get('setting.footer_html', ''),
            'beian' => $beian,
            'beian_number' => $matches[0] ?? '',
            'captcha' => json_encode($captcha)
        ]);
    }

    public function account(Request $request): \support\Response
    {
        // 每个ip每分钟只能调用10次
        try {
            Limit::perMinute($request->getRealIp(), 10);
        } catch (BusinessException $e) {
            return $this->error($e->getMessage());
        }

        $username = $request->post('username');
        $password = $request->post('password');
        $ticket   = $request->post('ticket');

        try {
            CaptchaCode::verify('account_login', $ticket, ['check_code' => 'check-mobile']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }


        $user = User::where('username', $username)->orWhere('email', $username)->orWhere('mobile', $username)->first();

        if (empty($user)){
            return json(['code' => 1, 'msg' => '用户不存在']);
        }

        if (empty($user->password)){
            return json(['code' => 1, 'msg' => '用户未设置密码,请选择其他方式登录']);
        }

        if (password_verify($password, $user->password)) {
            if ($user->status != 0) {
                return json(['code' => 1, 'msg' => '当前账户已经被禁用']);
            }
            Auth::login($user);
            // 发布登录事件
            Event::emit('user.login', $user);
            return json(['code' => 0, 'msg' => 'ok']);
        }

        return json(['code' => 1, 'msg' => '用户名或密码错误']);
    }



    /**
     * 判断手机号是否存在
     * @param Request $request
     * @return Response
     */
    public function checkAccount(Request $request): Response
    {
        $mobile      = $request->post('mobile', null);
        $region_code = $request->post('region_code', null);
        $ticket      = $request->post('ticket', null);

        if (empty($mobile)){
            return json(['code' => 1, 'msg' => '手机号不能为空']);
        }

        try {
            CaptchaCode::verify('account_check', $ticket, ['check_code' => 'check-mobile']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        $exists = User::where('mobile', $mobile)->exists();

        if (!$exists){
            return json(['code' => 404, 'msg' => '账号不存在']);
        }

        try {
            \plugin\signin\app\service\Sms::send($mobile, $this->CAPTCHA_MOBILE_KEY);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        return $this->success('ok');
    }

    public function smsLogin(Request $request): Response
    {
        $mobile      = $request->post('mobile');
        $region_code = $request->post('region_code');
        $sms_code    = $request->post('sms_code');
        $ticket      = $request->post('ticket');


        try {
            CaptchaCode::verify('sms_login', $ticket, ['check_code' => 'check-mobile']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        try {
            Captcha::validate('mobile', $mobile, $sms_code, session($this->CAPTCHA_MOBILE_KEY));
        } catch (BusinessException $e) {
            return json(['code' => $e->getCode(), 'msg' => $e->getMessage()]);
        }

        /** @var User $user */
        $user = User::where('mobile', $mobile)->first();

        if ($user->status != 0) {
            return json(['code' => 1, 'msg' => '当前账户已经被禁用']);
        }

        Auth::login($user);

        // 发布登录事件
        Event::emit('user.login', $user);

        return json(['code' => 0, 'msg' => 'ok']);

    }

}
