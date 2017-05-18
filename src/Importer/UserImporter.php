<?php

namespace Importer;

/**
 * Class UserImporter
 *
 * Creates a new user by importing data from a JSON file in the user_import dir.
 * Please refer to Confluence for a description of the expected schema.
 *
 * @package Importer
 */
class UserImporter extends JSONDirImporter {

    /**
     * Checks if the dataset is valid, i.e.
     * has a all required attributes with correct
     * values. Has to return true if the data is valid.
     *
     * @param $data array The dataset
     * @return boolean Result of validation
     */
    public function validate($data) {
        // check if the id field is there
        if (!$this->checkIfAllFieldsExist($data, ['id', 'isil'])) {
            $this->handleError($this->currentFile . ' is missing id and/or isil field');
            return false;
        }

        if (!$this->checkIfAllSubfieldsExist($data, ['budgets', 'defaults', 'suppliers'])) {
            $this->handleError($this->currentFile . ' does not contain a (valid) definition for a field');
            return false;
        }

        // check the budgets field
        if (!$this->checkNameValueListSubfield($data, 'budgets')) {
            $this->handleError($this->currentFile . ' has an error in the budgets field.');
            return false;
        }

        // check the supplier field
        if (!$this->checkNameValueListSubfield($data, 'suppliers')) {
            $this->handleError($this->currentFile . ' has an error in the suppliers field.');
            return false;
        }

        // check the defaults field
        if (!$this->checkIfAllFieldsExist($data['defaults'], ['selcode', 'ssgnr'])) {
            $this->handleError($this->currentFile . ' has an error in the defaults field.');
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
        $userData = [
            '_id' => $data['id'],
            'isil' => $data['isil'],
            'watchlists' => [
                [
                    'id' => uniqid(),
                    'name' => 'Meine Merkliste',
                    'default' => true
                ]
            ],
            'settings' => [
                'sortby' => 'erj',
                'order' => 'desc'
            ],
            'suppliers' => $data['suppliers'],
            'budgets' => $data['budgets'],
            'defaults' => $data['defaults']
        ];

        $this->databaseService->insertUser($userData);

        $this->log->addInfo('Import successful: ' . $this->currentFile);
        rename($this->currentFilePath, $this->config->getUsersDir() . $this->currentFile);
        $this->statsService->recordSuccessfulHandling($this);
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
            rename($this->currentFilePath, $this->config->getUsersFailDir() . $this->currentFile);
        }
        $this->statsService->recordFailedHandling($this);
    }

    /**
     * Further describes the purpose of the importer.
     *
     * @return string Description
     */
    public function getDescription() {
        return '';
    }
}
