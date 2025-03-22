<?php

namespace plugin\signin\app\admin\controller;

use plugin\admin\app\model\Option;
use plugin\signin\app\service\Config;
use plugin\signin\app\service\Filter;
use support\Request;

class SettingController extends Base
{

    public function save(Request $request): \support\Response
    {

        $post = $request->post();

        $data = Filter::htmlspecialchars($post);

        if (Config::set('setting', $data)){
            return $this->success('保存成功', $request->post());
        }else{
            return $this->error('保存失败', $request->post());
        }
    }

    public function login(Request $request): \support\Response
    {

        $post = $request->post();

        $data = Filter::htmlspecialchars($post);

        if (Config::set('login', $data)){
            return $this->success('保存成功', $request->post());
        }else{
            return $this->error('保存失败', $request->post());
        }
    }

    public function agreement(Request $request): \support\Response
    {

        $post = $request->post();

        $data = Filter::htmlspecialchars($post);

        if (Config::set('agreement', $data)){
            return $this->success('保存成功', $request->post());
        }else{
            return $this->error('保存失败', $request->post());
        }
    }

    public function captcha(Request $request): \support\Response
    {

        $key  = $request->get('key');
        $post = $request->post();

        if (!$key){
            return $this->error('参数错误', $request->post());
        }

        $data = Filter::htmlspecialchars($post);


        $value = Config::get('captcha.' . $key, []);
        $value[$key] = $data;

        if (Config::set('agreement', $value)){
            return $this->success('保存成功', $request->post());
        }else{
            return $this->error('保存失败', $request->post());
        }
    }
}
