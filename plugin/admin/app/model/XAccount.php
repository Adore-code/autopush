<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $type 账户类型
 * @property integer $user_id 用户id
 * @property string $x_consumer_key 
 * @property string $x_consumer_secret 
 * @property string $x_access_token 
 * @property string $x_access_token_secret 
 * @property string $x_client_id 
 * @property string $x_client_secret 
 * @property string $x_bearer_token 
 * @property string $x_account 推特账号
 * @property string $x_kol_list 自动转发的kol列表
 * @property integer $x_limit 限制推文长度
 * @property integer $x_time_diff 最小发文间隔
 * @property string $x_ai_template ai模板
 * @property string $x_ai_user ai角色
 */
class XAccount extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_account';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    
}
