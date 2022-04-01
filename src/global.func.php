<?php

if (!function_exists('error')) {
    /**
     * 构造错误数组.
     *
     * @param int $errno 错误码，0为无任何错误
     * @param string $message 错误信息
     *
     * @return array
     */
    function error($errno, $message = '')
    {
        return array(
            'errno'   => $errno,
            'message' => $message,
        );
    }
}

if (!function_exists('is_error')) {
    /**
     * 检测数组是否产生错误.
     *
     * @param mixed $data
     *
     * @return boolean
     */
    function is_error($data)
    {
        if (empty($data) || !is_array($data) || !array_key_exists('errno', $data) || (array_key_exists('errno', $data) && 0 == $data['errno'])) {
            return false;
        } else {
            return true;
        }
    }
}

if (!function_exists('is_error')) {
    /**
     * 判断字符串是否包含子串.
     *
     * @param string $string 在该字符串中进行查找
     * @param string $find 需要查找的字符串
     *
     * @return boolean
     */
    function strexists($string, $find)
    {
        return !(false === strpos($string, $find));
    }
}

if (!function_exists('random')) {
    /**
     * 获取随机字符串.
     *
     * @param number $length 字符串长度
     * @param bool $numeric 是否为纯数字
     *
     * @return string
     */
    function random($length, $numeric = false)
    {
        $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
        if ($numeric) {
            $hash = '';
        } else {
            $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
            --$length;
        }
        $max = strlen($seed) - 1;
        for ($i = 0; $i < $length; ++$i) {
            $hash .= $seed[mt_rand(0, $max)];
        }

        return $hash;
    }
}