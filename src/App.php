<?php

use Util\Log;
use Util\Config;

class App{

    public function run(){

        $init = new Initializer();
        $init->run();

        $config = Config::getInstance();

        $cmd = new Commando\Command();

        $cmd->flag('full')
            ->describeAs('Starts a full import')
            ->boolean();

        $cmd->flag('remote')
            ->describeAs('Fetch data from the remote computer')
            ->boolean();

        $cmd->flag('import-titles')
            ->describeAs('Import titles from the import directory')
            ->boolean();

        $cmd->flag('update-titles')
            ->describeAs('Update titles from the title update directory')
            ->boolean();

        $cmd->flag('covers')
            ->describeAs('Import covers')
            ->boolean();

        $cmd->flag('import-users')
            ->describeAs('Import user from the user import directory')
            ->boolean();

        $cmd->flag('update-users')
            ->describeAs('Update user from the user update directory')
            ->boolean();

        $cmd->flag('v')
            ->aka('verbose')
            ->describeAs('Show details')
            ->boolean();

        if($cmd['v']){
            Log::setVerbose(true);
        }

        $log = Log::getInstance()->getLog();
        $log->addInfo(print_r($cmd->getFlagValues(), true));

        $full = $cmd['full'];

        $flagsSet = false;
        foreach($cmd->getFlagValues() as $flag => $value){

            if($flag === 'v' || $flag === 'verbose'){
                continue;
            }

            $flagsSet = $flagsSet || $value;
        }

        if(!$flagsSet){
            $full = true;
        }

        if ($full || $cmd['remote']){
            $r = new RemoteFetcher();
            $r->run();
        }

        if ($full || $cmd['import-titles']){
            $t = new TitleImporter();
            $t->run();
        }

        if ($full || $cmd['update-titles']){
            $log->addInfo('Title Updater not implemented so far!');
        }

        if ($full || $cmd['covers']){
            $c = new CoverImporter();
            $c->run();
        }

        if ($full || $cmd['import-users']){
            $u = new UserImporter();
            $u->run();
        }

        if ($full || $cmd['update-users']){
            $uu = new UserUpdater();
            $uu->run();
        }

        if($config->getValue('logging', 'enable_mail')) {

            $msg = "Summary: \n";
            $msg .=  "Imported titles: " . $t->getTotal() . " (failed: " . $t->getFails() . ")\n";
            $msg .= "Updated titles: " . '-' . " (failed: " . '-' . ")\n";
            $msg .= "\n";
            $msg .= "Imported users: " . $u->getTotal() . " (failed: " . $u->getFails() . ")\n";
            $msg .= "Updated users: " . $uu->getTotal() . " (failed: " . $uu->getFails() . ")\n";
            $msg .= "\n";
            $msg .= "Covers checked: " . $c->getChecked() . " (without a cover: " . $c->getWithoutCover() . ")\n";


            $mail = new PHPMailer();

            $mail->From = 'import@online-profildienst.gbv.de';
            $mail->FromName = 'Profildienst Import';
            $mail->addAddress($config->getValue('logging', 'mail'));

            /*
            $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
            $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
            */

            $mail->Subject = 'Import finished!';
            $mail->Body    = $msg;

            if(!$mail->send()) {
                $log->addError('Message could not be sent.');
                $log->addError($mail->ErrorInfo);
            } else {
                $log->addInfo('Message has been sent');
            }
            
        }
    }

}