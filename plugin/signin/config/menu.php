<?php

return [
    [
        'title' => '登录管理',
        'key' => 'queue',
        'icon' => 'layui-icon-align-left',
        'weight' => 0,
        'type' => 0,
        'children' => [
            [
                'title' => '登录设置',
                'key' => \plugin\signin\app\admin\controller\IndexController::class,
                'href' => '/app/signin/admin/index/index',
                'type' => 1,
                'weight' => 0,
            ],
        ]
    ]
];
