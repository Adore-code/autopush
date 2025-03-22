<?php

namespace plugin\signin\app\service;

use plugin\user\app\model\User;
use support\Redis;

class Auth
{
    public static function login(User $user): void
    {
        $uuid = md5(uniqid($user->id));
        self::setSession($user, $uuid);
        self::setRedis($user, $uuid);
    }

    public static function setSession(User $user, $uuid): void
    {
        request()->session()->set('user', [
            'id' => $user->id,
            'uuid' => $uuid,
            'username' => $user->username,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'email' => $user->email,
            'mobile' => $user->mobile,
        ]);
    }

    public static function setRedis(User $user, $uuid): void
    {
        return;
        $loginMap = \support\Redis::hGetAll('login:map:' . $user->id);
        if ($loginMap){
            //提取$loginMap里面的time字段作为key，方便排序


            foreach ($loginMap as $key => $value) {
                $loginMap[$key] = json_decode($value, true);
            }

            //将$loginMap按照time字段降序
            uasort($loginMap, function ($a, $b){
                return $b['time'] - $a['time'];
            });

            if (count($loginMap) >= 3){
                $oldMap = array_slice($loginMap, 2);
                //提取$oldMap里面的uuid字段作为key，方便删除
                $keys = array_keys($oldMap);

                \support\Redis::hDel('login:map:' . $user->id, ...$keys);
            }
        }

        \support\Redis::hSet('login:map:' . $user->id, $uuid, json_encode(['uuid' => $uuid, 'ip' => request()->getRealIp(), 'time' => time()]));
    }

    public static function checkRedis($loginUserId, $loginUserUuid)
    {
        return true;
        $redisInfo = Redis::hGet('login:map:' . $loginUserId, $loginUserUuid);

        if (!empty($redisInfo)){
            $redisInfo = json_decode($redisInfo, true);
            if ($redisInfo['time'] > time() - (86400 * 7)) {
                return false;
            }
        }
        return true;
    }
}
