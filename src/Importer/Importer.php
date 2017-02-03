<?php

namespace Importer;

use Config\Config;
use Monolog\Logger;
use Pimple\Container;
use Util\Database;
use Util\Log;

/**
 * Interface Importer
 *
 * This is a rather generic marker interface for all kinds
 * of steps in the importing process
 */
abstract class Importer{

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Log
     */
    protected $logger;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var Logger logger
     */
    protected $log;

    public function __construct(Container $container){
        $this->config = $container['config'];
        $this->logger = $container['log'];
        $this->database = $container['database'];

        $this->log = $this->logger->getLog();
    }

    /**
     * Starts the importing step
     *
     * @return void
     */
    public abstract function run();

    /**
     * Returns the total amount of processed records
     *
     * @return int total amount
     */
    public abstract function getTotal();

    /**
     * Returns the number of records which could not be
     * imported.
     *
     * @return int failed records
     */
    public abstract function getFails();

}