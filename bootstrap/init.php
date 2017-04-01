<?php

use Config\Config;
use Config\ConfigCreator;
use Cover\Amazon;
use Importer\UserImporter;
use Importer\UserUpdater;
use Services\DatabaseService;
use Services\LogService;
use Services\MailerService;
use Services\ValidatorService;

function initContainer(\Pimple\Container $container) {
    $container['config'] = function ($container) {
        return new Config($container['configCreator']);
    };

    $container['mailerService'] = function ($container) {
        return new MailerService($container['logService'], $container['config']);
    };

    $container['logService'] = function ($container) {
        return new LogService($container['config']);
    };

    $container['configCreator'] = function ($container) {
        return new ConfigCreator();
    };

    $container['databaseService'] = function ($container) {
        return new DatabaseService($container['config']);
    };

    $container['validatorService'] = function ($container) {
        return new ValidatorService($container['config'], $container['databaseService'],
            $container['logService'], $container['mailerService']);
    };

    $container['coverService'] = function ($container) {
        return new Amazon($container['config'], $container['logService']);
    };

    $container['userUpdater'] = function ($container) {
        $dir = $container['config']->getValue('dirs', 'user_update');
        return new UserUpdater($container['config'], $container['logService'], $container['databaseService'], $dir);
    };

    $container['userImporter'] = function ($container) {
        $dir = $container['config']->getValue('dirs', 'user_import');
        return new UserImporter($container['config'], $container['logService'], $container['databaseService'], $dir);
    };
}