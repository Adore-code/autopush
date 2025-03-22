<?php

return [
    '@' => [
        \plugin\signin\app\middleware\RouteControl::class
    ],
    'admin' => [
        plugin\admin\api\Middleware::class
    ]
];
