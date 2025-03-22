<?php

namespace plugin\signin\app\service;

class Filter
{
    public static function htmlspecialchars(array $data): array
    {
        $list = [];
        foreach ($data as $key => $value) {
            if (is_array($value)){
                $list[$key] = self::htmlspecialchars($value);
            }else{
                $list[$key] = htmlspecialchars($value);
            }
        }

        return $list;
    }
}