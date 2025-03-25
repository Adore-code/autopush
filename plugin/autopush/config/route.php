<?php

use Webman\Route;

Route::any('/app/autopush/auth/captcha/{type}', [\plugin\autopush\app\controller\AuthController::class, 'captcha']);
