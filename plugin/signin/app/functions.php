<?php
/**
 * Here is your custom functions.
 */


function get_arrays_key_value($array, $key, $default = null)
{
    if ($key === null){
        return $default;
    }

    if (!is_array($array)){
        return $default;
    }

    // list($key1, $key2) = explode('.', $key, 2);
    $keyList = explode('.', $key, 2);
    $key1 = $keyList[0] ?? null;
    $key2 = $keyList[1] ?? null;


    if ($key2 === null){
        return $array[$key1] ?? $default;
    }

    if (!isset($array[$key1])){
        return $default;
    }

    return get_arrays_key_value($array[$key1], $key2, $default);
}

/**
 * @param array $array
 * @param string $key
 * @param mixed $value
 * @return array
 */
function set_arrays_key_value(&$array, $key, $value)
{
//    list($key1, $key2) = @explode('.', $key, 2);
    $keyList = explode('.', $key, 2);
    $key1 = $keyList[0] ?? null;
    $key2 = $keyList[1] ?? null;

    if ($key2 === null) {
        $array[$key1] = $value;
        return $array;
    }

    if (!is_array($array[$key1])) {
        $array[$key1] = [];
    }

    set_arrays_key_value($array[$key1], $key2, $value);
    return $array;
}
