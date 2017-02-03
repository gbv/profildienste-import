<?php
namespace Importer;

use Pimple\Container;

class UserUpdater extends JSONDirImporter {

    public function __construct(Container $container) {
        parent::__construct($container);
        $this->dir = $this->config->getValue('dirs', 'user_update');
    }

    /**
     * Checks if the dataset is valid, i.e.
     * has a all required attributes with correct
     * values. Has to return true if the data is valid.
     *
     * @param $data The dataset
     * @return boolean Result of validation
     */
    public function validate($data) {

        // check if the id field is there
        if (!$this->checkField($data, 'id')) {
            $this->handleError($this->currentFile, $this->currentFile . ' is missing id field');
            return false;
        }

        // the id field has to be astring
        if (!is_string($data['id'])) {
            $this->handleError($this->currentFile, $this->currentFile . ' has not a string as the id');
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
        if (isset($data['defaults'])){
            if (!$this->checkIfAllFieldsExist($data, ['selcode', 'ssgnr'])) {
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
    }

    /**
     * Error handler for invalid or unparseable files.
     *
     * @param $fileName
     * @param $reason
     * @param null $data
     * @return void
     */
    public function handleError($fileName, $reason, $data = null) {
        $this->log->addWarning($reason);
        if (!is_null($fileName)) {
            rename($fileName, $this->config->getValue('dirs', 'temp', true) . 'fail/' . $fileName);
        }
        $this->fails++;
    }
}
