<?php

namespace Util;


use Exception;

class Util {

    private function __construct() {
    }

    public static function addTrailingSlash($path) {
        return rtrim($path, '/') . '/';
    }

    public static function checkAndCreateDir($path) {

        if (is_dir($path)) {
            return null;
        }

        if (mkdir($path, 0777, true)) {
            return true;
        } else {
            return false;
        }
    }

    public static function format($val) {
        return ($val >= 0) ? $val : '-';
    }

    public static function getFormattedStat($arr, $key, $sub) {
        if (array_key_exists($key, $arr)) {
            if (array_key_exists($sub, $arr[$key])) {
                return Util::format($arr[$key][$sub]);
            }
        }
        return '-';
    }

    public static function createDir($path) {
        if (!Util::checkAndCreateDir($path)) {
            throw new Exception('Couldn\'t create '.$path.'!');
        }
        return Util::addTrailingSlash($path);
    }

    /**
     * See: http://php.net/manual/en/function.array-merge-recursive.php
     *
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function array_merge_recursive_distinct(array & $array1, array & $array2) {
        $merged = $array1;

        foreach ($array2 as $key => & $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::array_merge_recursive_distinct($merged[$key], $value);
            } else if (is_numeric($key)) {
                if (!in_array($value, $merged))
                    $merged[] = $value;
            } else
                $merged[$key] = $value;
        }

        return $merged;
    }
}