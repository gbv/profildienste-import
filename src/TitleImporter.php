<?php

use Util\Log;
use Util\Config;
use Util\Database;

class TitleImporter implements Importer{

    private $total = 0;
    private $fails = 0;

    private $mailer;

    public function __construct(){
        $this->mailer = App::getInstance()->getMailer();
    }

    public function run(){

        $log = Log::getInstance()->getLog();
        $db = Database::getInstance();

        $prices = 0;
        $count = 0;

        $handle = opendir(Config::getInstance()->getValue('dirs', 'title_import'));
        if ($handle === false) {
            $log->addError('Opening directory ' . Config::getInstance()->getValue('dirs', 'title_import') . 'failed!');
            return;
        }

        while (($file = readdir($handle)) !== false) {

            $f = Config::getInstance()->getValue('dirs', 'title_import', true).$file;
            if ($file !== '.' && $file !== '..' && pathinfo($f, PATHINFO_EXTENSION) === 'json') {

                $this-> total++;

                $d=json_decode(file_get_contents($f), true);
                if (is_null($d)){
                    $log->addWarning($f.' is not a valid json file');
                    continue;
                }

                $d['_id']=isset($d['006G']['0'])? $d['006G']['0'] : NULL;
                if (is_null($d['_id'])){
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true).'fail/'.$file);
                    $log->addWarning($f.' has no ID (Field 006G/0)!');
                    $this->fails++;
                    continue;
                }

                if(isset($d['004A']['f'])){
                    preg_match_all('/EUR (\d+.{0,1}\d{0,2})/', $d['004A']['f'], $m);

                    if(count($m) == 2 && count($m[1]) == 1){
                        $prices+= floatval($m[1][0]);
                        $count++;
                    }
                }

                $d['XX02']=NULL;

                try {
                    $db->insertTitle($d);
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true).$file);
                    $log->addInfo($f.' ok.');
                    foreach($d['XX01'] as $user){
                        $this->mailer->addTitle($user);
                    }
                } catch (\MongoCursorException $mce) {
                    $log->addError($mce);
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true).'fail/'.$file);
                    $this->fails++;
                } catch (\MongoCursorTimeoutException $mcte) {
                    $log->addError('Timeout-Error: '.$mcte);
                    rename($f, Config::getInstance()->getValue('dirs', 'temp', true).'fail/'.$file);
                    $this->fails++;
                }

            }
        }

        closedir($handle);

        if($count > 0){

            $cursor_price = $db->getGlobalPrice();
            $cursor_count = $db->getGlobalCount();

            if(!is_null($cursor_price) && !is_null($cursor_count)){

                $opr = $cursor_price['value'];
                $ocnt = $cursor_count['value'];

                $mean = round((($opr + $prices) / ($ocnt + $count)),2);

                try {
                    $db->updateData('gprice', ($opr + $prices));
                    $db->updateData('gcount', ($ocnt + $count));
                    $db->updateData('mean', $mean);
                }
                catch (\MongoCursorException $mce) {
                    $log->addError('Error: '.$mce);
                }
                catch (\MongoCursorTimeoutException $mcte) {
                    $log->addError('Timeout-Error: '.$mcte);
                }
                catch(\Exception $e){
                    $log->addError('Error: '.$e->getMessage());
                }

            }else{

                $mean = round(($prices / $count),2);

                try {
                    $db->insertData(array('_id' => 'mean', 'value' => $mean));
                    $db->insertData(array('_id' => 'gprice', 'value' => $prices));
                    $db->insertData(array('_id' => 'gcount', 'value' => $count));
                }
                catch (MongoCursorException $mce) {
                    $log->addError('Error: '.$mce->getMessage());
                }
                catch (MongoCursorTimeoutException $mcte) {
                    $log->addError('Timeout-Error: '.$mcte->getMessage());
                }

            }

        }
    }

    public function getTotal(){
        return $this->total;
    }

    public function getFails(){
        return $this->fails;
    }
}