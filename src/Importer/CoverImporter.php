<?php
namespace Importer;

use Config\Config;
use Services\LogService;
use Cover\CoverProvider;
use Services\DatabaseService;

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
     * @var int Stores how many titles have been checked for covers
     */
    private $checked = 0;

    /**
     * @var int Amount of titles without a cover
     */
    private $withoutCover = 0;

    /**
     * CoverImporter constructor.
     * @param Config $config
     * @param LogService $logService
     * @param DatabaseService $databaseService
     * @param CoverProvider $coverProvider
     */
    public function __construct(Config $config, LogService $logService, DatabaseService $databaseService, CoverProvider $coverProvider) {
        parent::__construct($config, $logService, $databaseService);
        $this->coverProvider = $coverProvider;
    }

    public function run() {

        $cursor = $this->database->findTitlesWithNoCover();

        foreach ($cursor as $d) {

            $this->checked++;

            $covers = $this->coverProvider->getCovers($d);

            if (!$covers) {
                $this->withoutCover++;
            }

            $this->database->updateCover($d, $covers);
        }
    }

    /**
     * Returns the total amount of processed records
     *
     * @return int total amount
     */
    public function getTotal() {
        return $this->checked;
    }

    /**
     * Returns the number of records which could not be
     * imported.
     *
     * @return int failed records
     */
    public function getFails() {
        return $this->withoutCover;
    }
}