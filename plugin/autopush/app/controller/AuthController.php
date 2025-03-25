<?php

namespace plugin\autopush\app\controller;

use plugin\admin\app\model\Admin;
use plugin\user\api\Limit;
use plugin\user\app\service\Register;
use support\Request;
use support\Response;
use support\think\Db;
use Webman\Captcha\CaptchaBuilder;
use Webman\Captcha\PhraseBuilder;

class AuthController
{
    // 登录页面
    public function login(Request $request)
    {
        return view('auth/login', [
            'title' => 'AI门通行证',
            'footer_text' => '登录或注册时仅使用APP购买'
        ]);
    }

    // 注册页面
    public function register(Request $request)
    {
        if ($request->method() === 'GET') {
            return view('auth/register', [
                'title' => 'AIT通行证',
                'reg_time' => '2021-01-03 10:00:00'
            ]);
        }

        // 每个ip每分钟只能调用10次
        Limit::perMinute($request->getRealIp(), 10);

        // 收集数据
        $username = trim($request->post('username', ''));
        $password = $request->post('password');
        $repassword = $request->post('repassword');
        $nickname = trim($request->post('nickname', ''));
        $imageCode = $request->post('captcha');
        $nickname = $nickname ?: $username;

        if(in_array($username, ['admin', 'administrator', 'manager', 'guanliyuan']))
        {
            return json(['code' => 1, 'msg' => '用户名不得含有敏感字符，请重新输入', 'data' => [
                'field' => 'username'
            ]]);
        }
        // 长度验证
        if (strlen($username) < 4) {
            return json(['code' => 1, 'msg' => '用户名至少4个字符', 'data' => [
                'field' => 'username'
            ]]);
        }

        // $username不能是纯数字
        if (is_numeric($username)) {
            return json(['code' => 1, 'msg' => '用户名不能是纯数字', 'data' => [
                'field' => 'username'
            ]]);
        }

        // $username不能带@符号
        if (strpos($username, '@') !== false) {
            return json(['code' => 1, 'msg' => '用户名不能带@符号', 'data' => [
                'field' => 'username'
            ]]);
        }

        if (strlen($password) < 6) {
            return json(['code' => 1, 'msg' => '密码至少6个字符', 'data' => [
                'field' => 'password'
            ]]);
        }

        if ($repassword != $password) {
            return json(['code' => 1, 'msg' => '两次密码不一致', 'data' => [
                'field' => 'password'
            ]]);
        }

        // 用户数据
        $user = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'nickname' => $nickname,
        ];

        // 获取注册配置
        $settings = Register::getSetting();

        // 图形验证码验证
        if ($settings['captcha_enable']) {
            $captchaData = session('captcha-register');
            if (strtolower($imageCode) !== $captchaData) {
                return json(['code' => 1, 'msg' => '图形验证码错误', 'data' => [
                    'field' => 'captcha'
                ]]);
            }
        }

        // 用户名唯一性验证
        if (Admin::where('username', $username)->first()) {
            return json(['code' => 1, 'msg' => '用户名已经被占用', 'data' => ['field' => 'username']]);
        }

// 注册用户
        Db::startTrans();
        try {
            $waUser = new \stdClass();
            foreach ($user as $key => $value) {
                $waUser->$key = $value;
            }
            $waUser->avatar = '/app/user/default-avatar.png';
            $waUser->created_at = date('Y-m-d H:i:s');
            //$waUser->join_ip = $request->getRealIp();
            $waUser->status  = 1;
            $waUser->vip     = 1;
            $waUser->invite_code = 1;
            $waUser->expired_at = time() + 60 * 60 * 24 * 7;

            $userId = Db::name('wa_admins')->insertGetId((array)$waUser);
            Db::name('wa_admin_roles')->save(['role_id' => 2, 'admin_id' => $userId]);

            Db::commit();
        } catch (\Throwable $exception) {
            Db::rollBack();
        }

        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 验证码
     * @param Request $request
     * @param string $type
     * @return Response
     */
    public
    function captcha(Request $request, string $type = 'login'): Response
    {
        $builder = new PhraseBuilder(4, 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ');
        $captcha = new CaptchaBuilder(null, $builder);
        $captcha->build(120);
        $request->session()->set("captcha-$type", strtolower($captcha->getPhrase()));
        $img_content = $captcha->get();
        return response($img_content, 200, ['Content-Type' => 'image/jpeg']);
    }

    public function agreement()
    {
        return view('auth/agreement', [
            'title' => 'AI门通行证',
            'footer_text' => '登录或注册时仅使用APP购买'
        ]);
    }
}