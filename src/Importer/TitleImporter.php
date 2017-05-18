<?php

namespace Importer;

use MongoDB\BSON\UTCDateTime;
use Util\ValidatorUtils;

/**
 * Class TitleImporter
 *
 * Imports titles from JSON files in the titles_import directory
 */
class TitleImporter extends JSONDirImporter {

    use ValidatorUtils;

    private $prices = 0;
    private $recordedPrices = 0;

    /**
     * Further describes the purpose of the importer.
     *
     * @return string Description
     */
    public function getDescription() {
        return '';
    }

    /**
     * Checks if the dataset is valid, i.e.
     * has a all required attributes with correct
     * values. Has to return true if the data is valid.
     *
     * @param $data array dataset
     * @return boolean Result of validation
     */
    public function validate($data) {

        if (!$this->checkField($data, '003@', '0')) {
            $this->handleError($this->currentFile . ' has no PPN.');
            return false;
        }

        return true;
    }

    /**
     * Handler for valid data
     *
     * @param $data
     * @return void
     */
    public function handleData($data) {

        // initialize various meta fields
        $fields = ['XX02', 'comment', 'ssgnr', 'selcode', 'budget', 'supplier', 'watchlist'];
        foreach ($fields as $field) {
            $data[$field] = null;
        }

        $data['lastStatusChange'] = new UTCDateTime((time() * 1000));

        // create a title database entry for each user
        $errorOccurred = false;
        foreach ($data['XX01'] as $user) {

            // the format for the id is PPN_USERID
            $data['_id'] = $data['003@']['0'] . '_' . $user;
            $data['user'] = $user;


            try {
                $this->databaseService->insertTitle($data);
                $this->log->addInfo('Import successful for user ' . $user . ': ' . $this->currentFile);
                $this->statsService->recordTitleImport($user);
            } catch (\Exception $e) {
                $this->log->addError($e->getMessage());
                $errorOccurred = true;
            }
        }

        if ($errorOccurred) {
            $this->handleError('An error occurred while importing ' . $this->currentFile . '. See above for details.');
        } else {

            // check if the title contains price information (used for mean price calculation for unknown titles)
            if (isset($data['004A']['f'])) {
                preg_match_all('/EUR (\d+.{0,1}\d{0,2})/', $data['004A']['f'], $m);
                if (count($m) === 2 && count($m[1]) === 1) {
                    $this->prices += floatval($m[1][0]);
                    $this->recordedPrices++;
                }
            }

            rename($this->currentFilePath, $this->config->getTitlesDir() . $this->currentFile);
            $this->statsService->recordSuccessfulHandling($this);
        }

    }

    public function afterHandling() {
        //update the overall mean used for estimating unknown prices
        if ($this->recordedPrices > 0) {

            $cursor_price = $this->databaseService->getGlobalPrice();
            $cursor_count = $this->databaseService->getGlobalCount();

            if (!is_null($cursor_price) && !is_null($cursor_count)) {

                $prevPrice = $cursor_price['value'];
                $prevCount = $cursor_count['value'];

                $mean = round((($prevPrice + $this->prices) / ($prevCount + $this->recordedPrices)), 2);

                try {
                    $this->databaseService->updateData('gprice', ($prevPrice + $this->prices));
                    $this->databaseService->updateData('gcount', ($prevCount + $this->recordedPrices));
                    $this->databaseService->updateData('mean', $mean);
                } catch (\Exception $e) {
                    $this->log->addError('Error: ' . $e->getMessage());
                }

            } else {

                $mean = round(($this->prices / $this->recordedPrices), 2);

                try {
                    $this->databaseService->insertData(['_id' => 'mean', 'value' => $mean]);
                    $this->databaseService->insertData(['_id' => 'gprice', 'value' => $this->prices]);
                    $this->databaseService->insertData(['_id' => 'gcount', 'value' => $this->recordedPrices]);
                } catch (\Exception $e) {
                    $this->log->addError('Error: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Error handler for invalid or unparseable files.
     *
     * @param $reason
     * @return void
     */
    public function handleError($reason) {
        $this->log->addWarning($reason);
        if (!is_null($this->currentFilePath)) {
            rename($this->currentFilePath, $this->config->getTitlesFailDir() . $this->currentFile);
        }
        $this->statsService->recordFailedHandling($this);
    }
}