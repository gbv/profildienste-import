<?php

use Config\Config;
use Cover\Amazon;
use Importer\CoverImporter;
use Importer\TitleImporter;
use Importer\UserImporter;
use Importer\UserUpdater;
use Services\DatabaseService;
use Services\LogService;
use Services\MailerService;
use Services\StatsService;
use Services\ValidatorService;

/**
 * Initializes the DI container
 *
 * @param \Pimple\Container $container
 */
function initContainer(\Pimple\Container $container) {

    $container['config'] = function ($container) {
        return new Config();
    };

    $container['mailerService'] = function ($container) {
        return new MailerService($container['logService'], $container['config'], $container['statsService'],
            $container['resourceFolder']);
    };

    $container['logService'] = function ($container) {
        return new LogService($container['config']);
    };

    $container['databaseService'] = function ($container) {
        return new DatabaseService($container['config']);
    };

    $container['validatorService'] = function ($container) {
        return new ValidatorService($container['config'], $container['databaseService'],
            $container['logService'], $container['mailerService']);
    };

    $container['coverProvider'] = function ($container) {
        return new Amazon($container['config'], $container['logService']);
    };

    $container['coverImporter'] = function ($container) {
        return new CoverImporter($container['config'], $container['logService'], $container['databaseService'],
            $container['coverProvider'], $container['statsService']);
    };

    $container['userUpdater'] = function ($container) {
        $dir = $container['config']->getValue('dirs', 'user_update');
        return new UserUpdater($container['config'], $container['logService'], $container['databaseService'],
            $container['statsService'], $dir);
    };

    $container['userImporter'] = function ($container) {
        $dir = $container['config']->getValue('dirs', 'user_import');
        return new UserImporter($container['config'], $container['logService'], $container['databaseService'],
            $container['statsService'], $dir);
    };

    $container['remoteFetcher'] = function ($container) {
        return new \Importer\RemoteFetcher($container['config'], $container['logService'],
            $container['databaseService'], $container['statsService']);
    };

    $container['titleImporter'] = function ($container) {
        $dir = $container['config']->getValue('dirs', 'title_import');
        return new TitleImporter($container['config'], $container['logService'], $container['databaseService'],
            $container['statsService'], $dir);
    };

    $container['statsService'] = function ($container) {
        return new StatsService();
    };
}