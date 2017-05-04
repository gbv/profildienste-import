<?php

namespace Importer;

use Config\Config;
use Monolog\Logger;
use Services\LogService;
use Services\DatabaseService;
use Services\StatsService;

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
    protected $databaseService;

    /**
     * @var StatsService
     */
    protected $statsService;

    /**
     * @var Logger log
     */
    protected $log;

    public function __construct(Config $config, LogService $logService, DatabaseService $databaseService, StatsService $statsService){
        $this->config = $config;
        $this->logService = $logService;
        $this->databaseService = $databaseService;
        $this->statsService = $statsService;

        $this->log = $this->logService->getLog();
    }

    /**
     * Starts the importing step
     *
     * @return void
     */
    public abstract function run();

    /**
     * Returns the canonical name of the importer
     *
     * @return string Name to identify the importer
     */
    public function getName() {
        return get_class($this);
    }

    /**
     * Further describes the purpose of the importer.
     *
     * @return string Description
     */
    public abstract function getDescription();

}