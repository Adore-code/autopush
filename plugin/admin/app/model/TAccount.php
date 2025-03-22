<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id ID(主键)
 * @property integer $user_id 用户id
 * @property string $type 账户类型
 * @property string $x_account 推特账号
 * @property string $x_consumer_key Consumer Key
 * @property string $x_consumer_secret Consumer Secret
 * @property string $x_access_token Access Token
 * @property string $x_access_token_secret Token Secret
 * @property string $x_client_id Client ID
 * @property string $x_client_secret Client Secret
 * @property string $x_bearer_token Bearer Token
 * @property string $x_kol_list 自动转发的kol列表
 * @property string $x_limit 限制推文长度
 * @property integer $x_time_diff 最小发文间隔（分钟）
 * @property string $data_origin 推文来源
 * @property string $x_ai_template ai模板
 * @property string $x_ai_user ai角色
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class TAccount extends Base
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
