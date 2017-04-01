<?php
namespace Importer;

/**
 * Class UserUpdater
 *
 * Updates existing user data based on a JSON file in the user_update dir.
 * Please refer to Confluence for a description of the expected schema.
 *
 * @package Importer
 */
class UserUpdater extends JSONDirImporter {

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
        if (!$this->checkIfAllFieldsExist($data, ['id'])) {
            $this->handleError($this->currentFile, $this->currentFile . ' is missing id field');
            return false;
        }

        if (!$this->checkIfAnySubfieldExists($data, ['budgets', 'defaults', 'suppliers'])) {
            $this->handleError($this->currentFile, $this->currentFile . ' does not contain a (valid) definition for an updateable field');
            return false;
        }

        // check the budgets field
        if (isset($data['budgets']) && !$this->checkNameValueListSubfield($data, 'budgets')) {
            $this->handleError($this->currentFile, $this->currentFile . ' has an error in the budgets field.');
            return false;
        }

        // check the supplier field
        if (isset($data['suppliers']) && !$this->checkNameValueListSubfield($data, 'suppliers')) {
            $this->handleError($this->currentFile, $this->currentFile . ' has an error in the suppliers field.');
            return false;
        }

        // check the defaults field
        if (isset($data['defaults'])) {
            if (!$this->checkIfAllFieldsExist($data['defaults'], ['selcode', 'ssgnr'], true)) {
                $this->handleError($this->currentFile, $this->currentFile . ' has an error in the defaults field.');
                return false;
            }
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

        $query = ['$set' => []];

        foreach (['budgets', 'suppliers', 'defaults'] as $field) {
            if (isset($field)) {
                $query['$set'][$field] = $data[$field];
            }
        }

        $this->database->updateUser($data['id'], $query);

        $this->log->addInfo('User ' . $data['id'].' imported!');
        rename($this->currentFilePath, $this->config->getValue('dirs', 'temp', true) . 'user/' . $this->currentFile);
        $this->total++;
    }

    /**
     * Error handler for invalid or unparseable files.
     *
     * @param $reason
     * @param null $data
     * @return void
     */
    public function handleError($reason, $data = null) {
        $this->log->addWarning($reason);
        if (!is_null($this->currentFilePath)) {
            rename($this->currentFilePath, $this->config->getValue('dirs', 'temp', true) . 'fail / ' . $this->currentFile);
        }
        $this->fails++;
    }
}
