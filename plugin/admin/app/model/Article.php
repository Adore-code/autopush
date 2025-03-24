<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id ID(主键)
 * @property string $x_account 推特账号
 * @property integer $source_id 文章来源id
 * @property string $source_content 新闻内容
 * @property string $ai_content AI回复内容
 * @property integer $status 状态
 * @property string $source 来源
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $article_id 推文ID
 * @property string $public_at 发送时间
 */
class Article extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_article';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    
}
