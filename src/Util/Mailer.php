<?php
/**
 * Created by PhpStorm.
 * User: luca
 * Date: 14.07.15
 * Time: 16:28
 */

namespace Util;

use Util\Log;
use Util\Config;

class Mailer {

    private $record = array();

    private $log;
    private $config;

    public function __construct(){
        $this->record = array();

        $this->log = Log::getInstance()->getLog();
        $this->config = Config::getInstance();
    }


    public function addTitle($user){
        $this->initRecord($user);
        $this->record['ID'.$user]['titles']++;
    }

    private function initRecord($user){
        if(!isset($this->record['ID'.$user])){
            $this->record['ID'.$user] = array(
              'titles' => 0
            );
        }
    }


    public function sendMailTo($user, $email){

        if(isset($this->record['ID'.$user])){

            $r = $this->record['ID'.$user];

            $msg  = "Sehr geehrte Damen und Herren,\n\n";
            $msg .= "fuer Sie bzw. Ihre Institution wurden im Online Profildienst des GBV bereitgestellt: \n";
            $msg .= "\n";
            $msg .= ">\t".$r['titles']." Titel\n";
            $msg .= "\n";
            $msg .= "\n---\n";
            $msg .= "\nDiese E-Mail wurde automatisch generiert, bitte antworten Sie nicht auf diese E-Mail.\n";

            $mail = new \PHPMailer();

            $mail->From = 'import@online-profildienst.gbv.de';
            $mail->FromName = 'Profildienst Import';
            $mail->addAddress($email);


            $mail->Subject = 'Neuen Daten im Online Profildienst';
            $mail->Body = $msg;

            if (!$mail->send()) {
                $this->log->addError('Message could not be sent.');
                $this->log->addError($mail->ErrorInfo);
            } else {
                $this->log->addInfo('Message has been sent');
            }
        }
    }

}