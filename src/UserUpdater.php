<?php

use Util\Log;
use Util\Database;
use Util\Config;

class UserUpdater implements Importer {

    private $total = 0;
    private $fails = 0;

    public function run() {

        $log = Log::getInstance()->getLog();
        $db = Database::getInstance();

        $handle = opendir(Config::getInstance()->getValue('dirs', 'user_update'));
        if ($handle === false) {
            $log->addError('Opening directory ' . Config::getInstance()->getValue('dirs', 'user_update') . 'failed!');
            return;
        }

        while (($file = readdir($handle)) !== false) {

            $f = Config::getInstance()->getValue('dirs', 'user_update', true) . $file;
            if ($file !== '.' && $file !== '..' && pathinfo($f, PATHINFO_EXTENSION) === 'json') {

                $this->total++;

                $d = json_decode(file_get_contents($f), true);
                if (is_null($d)) {
                    $log->addWarning($f . ' is not a valid json file');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true) . 'fail/' . $file);
                    $this->fails++;
                    continue;
                }

                // check if all fields are there
                if (!$this->checkField($d, 'ID', '0')) {
                    $log->addWarning($f . ' is missing ID field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true) . 'fail/' . $file);
                    $this->fails++;
                    continue;
                }

                if (!$this->checkField($d, 'ISIL', '0')) {
                    $log->addWarning($f . ' is missing ISIL field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true) . 'fail/' . $file);
                    $this->fails++;
                    continue;
                }

                if (!$this->checkField($d, 'DEFAULTS', 'lieft')) {
                    $log->addWarning($f . ' is missing DEFAULTS/lieft field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true) . 'fail/' . $file);
                    $this->fails++;
                    continue;
                }

                if (!$this->checkField($d, 'DEFAULTS', 'budget')) {
                    $log->addWarning($f . ' is missing DEFAULTS/budget field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true) . 'fail/' . $file);
                    $this->fails++;
                    continue;
                }

                if (!$this->checkField($d, 'DEFAULTS', 'ssgnr')) {
                    $log->addWarning($f . ' is missing DEFAULTS/ssgnr field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true) . 'fail/' . $file);
                    $this->fails++;
                    continue;
                }

                if (!$this->checkField($d, 'DEFAULTS', 'selcode')) {
                    $log->addWarning($f . ' is missing DEFAULTS/selcode field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true) . 'fail/' . $file);
                    $this->fails++;
                    continue;
                }

                if (!$this->checkField($d, 'BUDGETS')) {
                    $log->addWarning($f . ' is missing BUDGETS field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true) . 'fail/' . $file);
                    $this->fails++;
                    continue;
                }

                $id = $d['ID']['0'];
                $isil = $d['ISIL'];

                if (!$db->userExists($id)) {
                    $log->addWarning($id . ' does not exist in database.');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true) . 'fail/' . $file);
                    $this->fails++;
                    continue;
                }


                $upd = array(
                    '$set' => array(
                        'budgets' => $d['BUDGETS'],
                        'isil' => $isil,
                        'defaults' => $d['DEFAULTS']
                    )
                );

                try {
                    $db->updateUser($id, $upd);
                    $log->addInfo('Update successful: ' . $f);
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true) . $file);
                } catch (\Exception $e) {
                    $log->addError('Error updating user: ' . $e->getMessage());
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true) . 'fail/' . $file);
                }

            }
        }

        closedir($handle);
    }

    private function checkField($data, $field, $subfield = null) {
        if (is_null($subfield)) {
            return isset($data[$field]) && !empty($data[$field]);
        } else {
            return isset($data[$field][$subfield]) && !empty($data[$field][$subfield]);
        }
    }

    public function getTotal() {
        return $this->total;
    }

    public function getFails() {
        return $this->fails;
    }
}
