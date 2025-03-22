<?php

namespace plugin\signin\app\service;

use plugin\admin\app\model\Option;

class Config
{

    private static $pluginName = 'signin';

    /**
     * @param $key
     * @param $default
     * @return mixed|null
     */
    private static function getDb($key, $default = null)
    {
        $option = self::$pluginName;

        $config = Option::where('name', $option)->value('value');

        if (!$config) {
            return $default;
        }

        $config = json_decode($config, true);

        return get_arrays_key_value($config, $key, $default);
    }

    private static function saveDb($key, $data): bool
    {
        $data = Filter::htmlspecialchars($data);

        if (!$item = Option::where('name', self::$pluginName)->first()) {
            $item = new Option;
            $item->value = "{}";
        }
        $value = @json_decode($item->value, true);
        $value = $value ?? [];
        $value = set_arrays_key_value($value, $key, $data);
        $item->name = self::$pluginName;
        $item->value = json_encode($value, JSON_UNESCAPED_UNICODE);
        return $item->save();
    }

    public static function getDefault($key, $default = null)
    {
        $option = 'plugin.signin.signin';

        if ($key){
            $option = $option . ".{$key}";
        }

        return config($option, $default);

    }

    public static function get($key, $default = null)
    {
        $config = self::getDb($key, null);

        if ($config === null){
            return self::getDefault($key, $default);
        }

        return $config;
    }

    public static function set($key, $value)
    {
        return self::saveDb($key, $value);
    }

}
