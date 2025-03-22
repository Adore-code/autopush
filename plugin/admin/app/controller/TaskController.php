<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\Task;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 我的任务 
 */
class TaskController extends Crud
{
    
    /**
     * @var Task
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Task;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('task/index');
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
            return parent::insert($request);
        }
        return view('task/insert');
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
            return parent::update($request);
        }
        return view('task/update');
    }

}
