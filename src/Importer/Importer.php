<?php

namespace Importer;

use Config\Config;
use Monolog\Logger;
use Services\LogService;
use Services\DatabaseService;

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
     * @var LogService
     */
    protected $logService;

    /**
     * @var DatabaseService
     */
    protected $database;

    /**
     * @var Logger log
     */
    protected $log;

    public function __construct(Config $config, LogService $logService, DatabaseService $databaseService){
        $this->config = $config;
        $this->logService = $logService;
        $this->database = $databaseService;

        $this->log = $this->logService->getLog();
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