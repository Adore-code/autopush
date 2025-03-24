<?php

namespace plugin\admin\app\controller;

use app\controller\TwitterController;
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

    public function postTest(Request $request):  Response
    {
        $accountID = $request->get('id');
        $account   = TAccount::find($accountID);
        $account   = $account->toArray();

        if (!$account) {
            return json(['error' => 'Account not found']);
        }

        $settings = [
            'account_id' => $account['x_account'],
            'access_token' => $account['x_access_token'],
            'access_token_secret' => $account['x_access_token_secret'],
            'consumer_key' => $account['x_consumer_key'],
            'consumer_secret' => $account['x_consumer_secret'],
            'bearer_token' => $account['x_bearer_token'],
        ];

        $twitter = new TwitterController();

        if ($request->method() === 'POST') {
            $post = $request->post();
            $content = strip_tags($post['content']);

            $message = '发送失败！请仔细检查API是否填写正确！';
            $response = [];
            $code     = 1;
            if($content)  $response = $twitter->createTweet($settings, $content);
            if($response)
            {
                if(isset($response->status))   $message = $response->detail;
                if(isset($response->data->id))
                {
                    $code = 0;
                    $message = "发送成功！ 推文链接：https://x.com/ahei750513/status/".$response->data->id;
                    TAccount::where('id', $accountID)->update(['status' => '1']);
                }
            }

            return $this->json($code, $message);
        }
        return view('t-account/test');
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
        $where = [
            'user_id' => $user_id,
            'deleted' => 0,
            'status'  => 1
        ];

        $accounts = TAccount::where($where)->pluck('x_account', 'id')->toArray();
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
