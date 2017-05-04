<?php

namespace Services;


use Importer\Importer;

/**
 * Class StatsService
 *
 * This service collects statistics about the total and failed amount of attempted imports per updater.
 *
 * @package Services
 */
class StatsService {

    private $stats = [];

    public function recordFailedHandling(Importer $importer) {
        $this->initCategory($importer);
        $this->stats[$importer->getName()]['failed']++;
        $this->stats[$importer->getName()]['total']++;
    }

    public function recordSuccessfulHandling(Importer $importer) {
        $this->initCategory($importer);
        $this->stats[$importer->getName()]['total']++;
    }

    public function getStats() {
        return $this->stats;
    }

    private function initCategory(Importer $importer) {
        if (!isset($this->stats[$importer->getName()])) {
            $this->stats[$importer->getName()] = [
                'failed' => 0,
                'description' => $importer->getDescription(),
                'total' => 0
            ];
        }
    }

}