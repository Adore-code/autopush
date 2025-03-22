<?php

use Webman\Http\Request;
use Webman\Route;

Route::any('/app/signin/login', [\plugin\signin\app\controller\LoginController::class, 'index']);
Route::any('/app/signin/register', [\plugin\signin\app\controller\RegisterController::class, 'index']);
Route::any('/app/signin/agreement', [\plugin\signin\app\controller\AgreementController::class, 'index']);
