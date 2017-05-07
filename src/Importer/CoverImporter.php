<?php
namespace Importer;

use Config\Config;
use Services\LogService;
use Cover\CoverProvider;
use Services\DatabaseService;
use Services\StatsService;

/**
 * Class CoverImporter
 *
 * Retrieves and saves the URL(s) of the covers of the titles in the database
 */
class CoverImporter extends Importer {

    /**
     * @var CoverProvider The actual Cover provider which should be used
     */
    private $coverProvider;

    /**
     * CoverImporter constructor.
     * @param Config $config
     * @param LogService $logService
     * @param DatabaseService $databaseService
     * @param CoverProvider $coverProvider
     * @param StatsService $statsService
     */
    public function __construct(Config $config, LogService $logService, DatabaseService $databaseService, CoverProvider $coverProvider, StatsService $statsService) {
        parent::__construct($config, $logService, $databaseService, $statsService);
        $this->coverProvider = $coverProvider;
    }

    public function run() {

        $this->statsService->init($this);

        while (!is_null(($title = $this->databaseService->findTitleWithNoCover()))) {

            $covers = $this->coverProvider->getCovers($title);

            if (!$covers) {
                $this->statsService->recordFailedHandling($this);
            } else {
                $this->statsService->recordSuccessfulHandling($this);
            }

            $this->databaseService->updateCover($title, $covers);
        }
    }

    /**
     * Further describes the purpose of the importer.
     *
     * @return string Description
     */
    public function getDescription() {
        return 'Cover Importer (Fails indicate that no covers were found)';
    }
}