<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id ID(主键)
 * @property integer $user_id 用户ID
 * @property string $x_account 推特账户
 * @property integer $push_interval 发文间隔（分钟）
 * @property string $source 新闻来源
 * @property integer $status 状态
 * @property integer $lowest_score 发推最低评分
 * @property integer $x_limit 限制推文长度
 * @property string $x_kol_list 自动转发的kol列表
 * @property string $x_ai_user ai角色扮演
 * @property string $x_ai_template ai描述模板
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class Task extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_task';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    
}
