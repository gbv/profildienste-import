<?php

use Util\Log;
use Util\Config;
use Util\Mailer;

class App{

    private static $instance;

    private $mailer;

    private function __construct(){

        $init = new Initializer();
        $init->run();

        $this->mailer = new Mailer();
    }

    public static function getInstance(){

        if(is_null(App::$instance)){
            App::$instance = new App();
        }

        return App::$instance;
    }

    public function getMailer(){
        return $this->mailer;
    }


    public function run(){

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

        $r = null;
        if ($full || $cmd['remote']){
            $r = new RemoteFetcher();
            $r->run();
        }

        $t = null;
        if ($full || $cmd['import-titles']){
            $t = new TitleImporter();
            $t->run();
        }

        if ($full || $cmd['update-titles']){
            $log->addInfo('Title Updater not implemented so far!');
        }

        $c = null;
        if ($full || $cmd['covers']){
            $c = new CoverImporter();
            $c->run();
        }

        $u = null;
        if ($full || $cmd['import-users']){
            $u = new UserImporter();
            $u->run();
        }

        $uu = null;
        if ($full || $cmd['update-users']){
            $uu = new UserUpdater();
            $uu->run();
        }


        if($config->getValue('mailer', 'enable')){
            $mapping = $config->getValue('mailer', 'mapping');

            foreach($mapping as $user => $emails){
                foreach($emails as $email){
                    $this->mailer->sendMailTo($user, $email);
                }

            }
        }

        if($config->getValue('logging', 'enable_mail')) {

            $titles = is_null($t) ? -1 : $t->getTotal();
            $titlesFailed = is_null($t) ? -1 : $t->getFails();

            $titleUpdates = -1;
            $titleUpdatesFailed = -1;

            $users = is_null($u) ? -1 : $u->getTotal();
            $usersFailed = is_null($u) ? -1 : $u->getFails();

            $userUpdates = is_null($uu) ? -1 : $uu->getTotal();
            $userUpdatesFailed = is_null($uu) ? -1 : $uu->getFails();

            $covers= is_null($c) ? -1 : $c->getChecked();
            $coversWithout= is_null($c) ? -1 : $c->getWithoutCover();

            if($titles > 0 || $users > 0 || $userUpdates > 0 || $covers > 0 || $titlesFailed > 0 || $usersFailed > 0 || $userUpdatesFailed > 0 || $coversWithout > 0){

                $msg = "Summary: \n";
                $msg .= "Imported titles: " . $this->format($titles) . " (failed: " . $this->format($titlesFailed) . ")\n";
                $msg .= "Updated titles: " . $this->format($titleUpdates) . " (failed: " . $this->format($titleUpdatesFailed) . ")\n";
                $msg .= "\n";
                $msg .= "Imported users: " . $this->format($users) . " (failed: " . $this->format($usersFailed) . ")\n";
                $msg .= "Updated users: " . $this->format($userUpdates) . " (failed: " . $this->format($userUpdatesFailed) . ")\n";
                $msg .= "\n";
                $msg .= "Covers checked: " . $this->format($covers) . " (without a cover: " . $this->format($coversWithout) . ")\n";


                $mail = new PHPMailer();

                $mail->From = 'import@online-profildienst.gbv.de';
                $mail->FromName = 'Profildienst Import';

                $emails = $config->getValue('logging', 'mail');
                foreach($emails as $email) {
                    $mail->addAddress($email);
                }

                if (filesize(Log::getInstance()->getLogPath()) > $config->getValue('logging','max_mailsize')){
                    $mail->addAttachment(Log::getInstance()->getLogPath(), 'Log.log');
                }else{
                    $msg.= "\n";
                    $msg.= "A log file has been generated, but it is too big to be attached.";
                    $msg.= "It can be found here: ".Log::getInstance()->getLogPath()."\n";
                }


                $mail->Subject = 'Import finished!';
                $mail->Body = $msg;

                if (!$mail->send()) {
                    $log->addError('Message could not be sent.');
                    $log->addError($mail->ErrorInfo);
                } else {
                    $log->addInfo('Message has been sent');
                }

            }else{
                $log->addInfo('No email sent since nothing happened.');
            }
        }
    }

    private function format($val){
        return ($val >= 0) ? $val : '-';
    }

}