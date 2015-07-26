<?php

namespace Util;


class Util {

    public static function addTrailingSlash($path){
        return rtrim($path, '/').'/';
    }
}