<?php

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            // 数据库类型
            'type' => 'mysql',
            // 服务器地址
            'hostname' => '127.0.0.1',
            // 数据库名
            'database' => 'autopush',
            // 数据库用户名
            'username' => 'root',
            // 数据库密码
            'password' => 'Zz11112222.',
            // 数据库连接端口
            'hostport' => '3306',
            // 数据库连接参数
            'params' => [
                // 连接超时3秒
                \PDO::ATTR_TIMEOUT => 3,
            ],
            // 数据库表前缀
            'prefix' => '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            // 断线重连
            'break_reconnect' => true,
            // 自定义分页类
            'bootstrap' =>  '',
            // 连接池配置(仅在swow/swoole驱动下有效)
            'pool' => [
                'max_connections' => 5,
                'min_connections' => 1,
                'wait_timeout' => 3,
                'idle_timeout' => 60,
                'heartbeat_interval' => 50,
            ],
        ],
    ],
];
