<?php

use Util\Log;
use Util\Database;
use Util\Config;

class UserImporter implements Importer{

    private $total = 0;
    private $fails = 0;

    public function run(){

        $log = Log::getInstance()->getLog();

        $handle = opendir(Config::getInstance()->getValue('dirs', 'user_import'));
        if ($handle === false) {
            $log->addError('Opening directory ' . Config::getInstance()->getValue('dirs', 'user_import') . 'failed!');
            return;
        }

        //setup title temp directory
        Config::getInstance()->setupUserTempDir();

        while (($file = readdir($handle)) !== false) {

            $f = Config::getInstance()->getValue('dirs', 'user_import', true) . $file;
            if ($file !== '.' && $file !== '..' && pathinfo($f, PATHINFO_EXTENSION) === 'json') {

                $this->total++;

                $d = json_decode(file_get_contents($f), true);
                if (is_null($d)) {
                    $log->addWarning($f . ' is not a valid json file');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true).'fail/'.$file);
                    $this->fails++;
                    continue;
                }

                // check if all fields are there
                if(!$this->checkField($d, 'ID', '0')){
                    $log->addWarning($f . ' is missing ID field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true).'fail/'.$file);
                    $this->fails++;
                    continue;
                }

                if(!$this->checkField($d, 'ISIL', '0')){
                    $log->addWarning($f . ' is missing ISIL field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true).'fail/'.$file);
                    $this->fails++;
                    continue;
                }

                if(!$this->checkField($d, 'SUPPLIERS')){
                    $log->addWarning($f . ' is missing SUPPLIERS field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true).'fail/'.$file);
                    $this->fails++;
                    continue;
                }

                // check each supplier
                foreach ($d['SUPPLIERS'] as $supplier){
                    if (empty($supplier['name']) || empty($supplier['value'])){
                        $log->addWarning($f . ' has an incomplete suppliers entry: '. print_r($supplier, true));
                        rename($f, Config::getInstance()->getValue('dirs', 'temp', true).'fail/'.$file);
                        $this->fails++;
                        continue;
                    }
                }

                if(!$this->checkField($d, 'DEFAULTS', 'budget')){
                    $log->addWarning($f . ' is missing DEFAULTS/budget field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true).'fail/'.$file);
                    $this->fails++;
                    continue;
                }

                if(!$this->checkField($d, 'DEFAULTS', 'ssgnr')){
                    $log->addWarning($f . ' is missing DEFAULTS/ssgnr field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true).'fail/'.$file);
                    $this->fails++;
                    continue;
                }

                if(!$this->checkField($d, 'DEFAULTS', 'selcode')){
                    $log->addWarning($f . ' is missing DEFAULTS/selcode field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true).'fail/'.$file);
                    $this->fails++;
                    continue;
                }

                if(!$this->checkField($d, 'BUDGETS')){
                    $log->addWarning($f . ' is missing BUDGETS field');
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true).'fail/'.$file);
                    $this->fails++;
                    continue;
                }

                $id = $d['ID']['0'];
                $isil = $d['ISIL']['0'];

                $dataset= [
                    '_id' => $id,
                    'budgets' => $d['BUDGETS'],
                    'suppliers' => $d['SUPPLIERS'],
                    'watchlists' => [
                        [
                            'id' => uniqid(),
                            'name' => 'Meine Merkliste',
                            'default' => true
                        ]
                    ],
                    'isil' => $isil,
                    'defaults' => $d['DEFAULTS'],
                    'settings' => [
                        'sortby' => 'erj',
                        'order' => 'desc'
                    ]
                ];

                try{
                    Database::getInstance()->insertUser($dataset);
                    $log->addInfo('Import successful: '.$f);
                    rename($f, Config::getInstance()->getTempSaveDir(true) . 'user/' . $file);
                }catch(\Exception $e){
                    $log->addError('Error importing user: '.$e->getMessage());
                    rename($f, Config::getInstance()->getTempSaveDir(true) . 'user/fail/' . $file);
                }

            }
        }

        closedir($handle);
    }

    private function checkField($data, $field, $subfield = null){
        if(is_null($subfield)){
            return isset($data[$field]) && !empty($data[$field]);
        }else{
            return isset($data[$field][$subfield]) && !empty($data[$field][$subfield]);
        }
    }

    public function getTotal(){
        return $this->total;
    }

    public function getFails(){
        return $this->fails;
    }
}
