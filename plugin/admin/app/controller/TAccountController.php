<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\TAccount;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 账号管理 
 */
class TAccountController extends Crud
{
    
    /**
     * @var TAccount
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new TAccount;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('t-account/index');
    }

    public function select(Request $request): Response
    {
        $user_id  = admin_id();
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);

        // 查询当前用户的推特账号
        $where['user_id'] = ['=', $user_id];
        $query = $this->doSelect($where, $field, $order);
        $paginator = $query->paginate($limit);
        $items = $paginator->items();

        return json(['code' => 0, 'msg' => 'ok', 'count' => $paginator->total(), 'data' => $items]);
    }

    public function getXAccounts(): Response
    {
        $user_id = admin_id();
        $accounts = TAccount::where(['user_id' => $user_id, 'deleted' => 0])->pluck('x_account', 'id')->toArray();
        $items = [];
        foreach ($accounts as $key => $account) {
            $items[] = [
                'name' =>$account,
                'value' => $account
            ];
        }
        return $this->json(0, 'ok', $items);
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
        return view('t-account/insert');
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
        return view('t-account/update');
    }

}
