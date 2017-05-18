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

    private $titleStats = [];

    public function recordFailedHandling(Importer $importer) {
        $this->init($importer);
        $this->stats[$importer->getName()]['failed']++;
        $this->stats[$importer->getName()]['total']++;
    }

    public function recordSuccessfulHandling(Importer $importer) {
        $this->init($importer);
        $this->stats[$importer->getName()]['total']++;
    }

    public function getStats() {
        return $this->stats;
    }

    public function init(Importer $importer) {
        if (!isset($this->stats[$importer->getName()])) {
            $this->stats[$importer->getName()] = [
                'failed' => 0,
                'description' => $importer->getDescription(),
                'total' => 0
            ];
        }
    }

    public function recordTitleImport($user) {
        if (!isset($this->titleStats[$user])) {
            $this->titleStats[$user] = 0;
        }

        $this->titleStats[$user]++;
    }

    public function getTitleStats() {
        return $this->titleStats;
    }

}