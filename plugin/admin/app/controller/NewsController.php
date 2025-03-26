<?php

namespace plugin\admin\app\controller;

use app\controller\ChatController;
use plugin\admin\app\model\Article;
use plugin\admin\app\model\Task;
use support\Request;
use support\Response;
use plugin\admin\app\model\News;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;
use support\think\Db;

/**
 * 最新资讯 
 */
class NewsController extends Crud
{
    
    /**
     * @var News
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new News;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('news/index');
    }

    public function reply(Request $request): Response
    {   $id = $request->get('id');
        $new = News::where('id', $id)->find($id);

        if ($request->method() === 'POST') {
            $x_account  = $request->post('x_account');
            $content    = $request->post('content');

            if (!$x_account) {
                return $this->json(1, '请选择推特账号！');
            }

            $template = Db::name('wa_task')->where('x_account', $x_account)->field('x_ai_template')->find();
            if (!$template) {
                return $this->json(1, '请先配置AI模板！');
            }

            $chat = new ChatController();
            $postData = ['ai_template' => $template['x_ai_template'], 'content' => $content];
            $reply    = $chat->reply($postData);

            if($reply['code'] == 200) return  $this->json(0, '获取成功', $reply);
            return $this->json(1, '获取失败', $reply);
        }
        return view('news/create', ['content' => $new['content']]);
    }

    public function create(Request $request): Response
    {   $id = $request->get('id');
        $new = News::where('id', $id)->find($id);

        if ($request->method() === 'POST') {
            $x_account  = $request->post('x_account');
            $content    = $request->post('content');
            $ai_content = $request->post('ai_content');
            $public_at  = $request->post('public_at');

            $todyArticle = Db::name('wa_article')->where('x_account', $x_account)->whereDay('public_at', 'today')
                ->count();
            if($todyArticle >= 15) return $this->json(1, '今天发文数量已达上限！');

            $public_time = strtotime($public_at);
            if($public_time - time() < 600) return $this->json(1, '发布时间不能小于当前时间！');
            if (!$x_account) {
                return $this->json(1, '请选择推特账号！');
            }

            $data = [
                'x_account'      => $x_account,
                'source_id'      => $id,
                'ai_content'     => $ai_content ?: $content,
                'status'         => 0,
                'created_at'     => date('Y-m-d H:i:s', time()),
                'public_at'      => $public_at,
                'source_content' => $content,
                'type' => 2
            ];

            Db::name('wa_article')->insert($data);

            return $this->json(0, '发布成功');
        }
        return view('news/create', ['content' => $new['content']]);
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
        return view('news/insert');
    }

    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);

        // 查询当前用户的推特账号
        if(empty($field))
        {
            $field = 'id';
            $order = 'desc';
        }
        $query = $this->doSelect($where, $field, $order);
        $paginator = $query->paginate($limit);
        $items = $paginator->items();

        return json(['code' => 0, 'msg' => 'ok', 'count' => $paginator->total(), 'data' => $items]);
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
        return view('news/update');
    }

}
