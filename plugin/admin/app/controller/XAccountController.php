<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\XAccount;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;
use support\View;

/**
 * 我的账号 
 */
class XAccountController extends Crud
{
    
    /**
     * @var XAccount
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new XAccount;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('x-account/index');
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $post = $request->post();
            $post['user_id'] = admin_id();
            $post['type']    = 'x';
            $request->setPost($post);
            return parent::insert($request);
        }
        return view('x-account/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException
    */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $post = $request->post();
            $post['user_id'] = admin_id();
            $post['type']    = 'x';
            $request->setPost($post);
            return parent::update($request);
        }
        return view('x-account/update');
    }

}
