<?php

namespace app\controller;

use support\Request;

class IndexController
{
    public function index(Request $request)
    {
        return view('index/view', ['name' => 'webman']);
    }

    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webman']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
