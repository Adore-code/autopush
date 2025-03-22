<?php
namespace plugin\signin\app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class RouteControl implements MiddlewareInterface
{

    /**
     * 增加全局路由劫持中间件
     * @param Request $request
     * @param callable $handler
     * @return Response
     */
    public function process(Request $request, callable $handler): Response
    {
        if (!in_array($request->plugin, ['user', 'gpt', 'ai'])){
            return $handler($request);
        }

        $controller = $request->controller;
        $action = $request->action;

        if ($controller == 'plugin\ai\app\controller\UserController' && $action == 'login'){
            return $this->redirectLogin($request);
        }

        if ($controller == 'plugin\gpt\app\controller\UserController' && $action == 'login'){
            return $this->redirectLogin($request);
        }

        if ($controller == 'plugin\user\app\controller\LoginController' && $action == 'index'){
            return $this->redirectLogin($request);
        }

        return $this->cors($request, $handler($request));
    }


    protected function redirectLogin(Request $request)
    {
        $query = $request->queryString();
        return $this->cors($request, redirect('/app/signin/login' . ($query? '?' . $query : '')));
    }

    /**
     * 跨域headers
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    protected function cors(Request $request, Response $response)
    {
        if (method_exists($response, 'exception') && $exception = $response->exception()) {
            $code = $exception->getCode() ?: 1;
            $msg = $exception->getMessage();
            $response = $request->expectsJson() ? json([
                'code' => $code,
                'msg' => $msg,
                'error' => ['message' => $msg],
                'data' => [],
                'traces' => config('plugin.ai.app.debug') ? $exception->getTraceAsString() : ''
            ]) : $response;
        }
        return $response->withHeaders([
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Origin' => $request->header('origin', '*'),
            'Access-Control-Allow-Methods' => $request->header('access-control-request-method', '*'),
            'Access-Control-Allow-Headers' => $request->header('access-control-request-headers', '*'),
        ]);
    }
}
