<?php

namespace plugin\autopush\app\controller;

use support\Request;

class IndexController
{

    public function index()
    {
        return view('index/help_x', ['name' => 'autopush']);
    }

}
