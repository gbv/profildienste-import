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
}