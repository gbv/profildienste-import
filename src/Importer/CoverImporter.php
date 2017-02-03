<?php

use Cover\CoverService;
use Pimple\Container;

/**
 * Class CoverImporter
 *
 * Retrieves and saves the URL(s) of the covers of the titles in the database
 */
class CoverImporter extends Importer {

    /**
     * @var CoverService The actual Cover provider which should be used
     */
    private $coverService;

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
     * @param Container $container
     */
    public function __construct(Container $container) {
        parent::__construct($container);
        $this->coverService = $container['coverService'];
    }

    public function run() {

        $cursor = $this->database->findTitlesWithNoCover();

        foreach ($cursor as $d) {

            $this->checked++;

            $covers = $this->coverService->getCovers($d);

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