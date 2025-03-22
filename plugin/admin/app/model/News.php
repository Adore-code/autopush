<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id ID(主键)
 * @property string $title 标题
 * @property string $sub_title 子标题
 * @property string $content 内容
 * @property string $link 原文链接
 * @property string $new_id 新闻ID
 * @property string $source 来源
 * @property integer $public_at 发布时间
 * @property integer $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $img_url 图片连接
 * @property string $base_link
 */
class News extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_news';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    
}
