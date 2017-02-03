<?php

use Config\Config;
use Config\ConfigCreator;
use Cover\Amazon;
use Util\Database;
use Util\Log;
use Util\Mailer;
use Util\Validator;

function initContainer(\Pimple\Container $container) {
    $container['config'] = function ($container) {
        return new Config($container['configCreator']);
    };

    $container['mailer'] = function ($container) {
        return new Mailer($container['log'], $container['config']);
    };

    $container['log'] = function ($container) {
        return new Log($container['config']);
    };

    $container['configCreator'] = function ($container) {
        return new ConfigCreator();
    };

    $container['database'] = function ($container) {
        return new Database($container['config']);
    };

    $container['validator'] = function ($container) {
        return new Validator($container['config'], $container['database'],
            $container['log'], $container['mailer']);
    };

    $container['coverService'] = function ($container) {
        return new Amazon($container['config'], $container['log']);
    };
}