<?php

use Util\Log;
use Util\Config;

class RemoteFetcher implements Importer{

    public function run(){

        $config = Config::getInstance();
        $log = Log::getInstance()->getLog();

        $host = $config->getValue('remote', 'user').'@'.$config->getValue('remote', 'host').':'.$config->getValue('remote', 'dir', true);
        exec('rsync -azPi --stats  --remove-source-files '.$host.' '.$config->getValue('dirs', 'title_import').' 2>&1', $output, $ret);

        foreach($output as $o){
            $log->addInfo($o);
        }

        if($ret != 0){
            $log->addError('Fetching from remote host failed! Please see output above.');
            exit(-1);
        }
    }
}