<?php
/**
 * Created by PhpStorm.
 * User: luca
 * Date: 14.07.15
 * Time: 16:28
 */

namespace Util;

use Config\Config;
use PHPMailer;

class Mailer {

    private $record = [];

    private $logger;
    private $config;

    private $log;

    public function __construct(Log $log, Config $config) {
        $this->logger = $log;
        $this->config = $config;

        $this->log = $this->logger->getLog();
    }


    public function addTitle($user) {
        $this->initRecord($user);
        $this->record['ID' . $user]['titles']++;
    }

    private function initRecord($user) {
        if (!isset($this->record['ID' . $user])) {
            $this->record['ID' . $user] = [
                'titles' => 0
            ];
        }
    }

    public function sendUserNotificationMail($user, $email) {

        if (isset($this->record['ID' . $user])) {

            $r = $this->record['ID' . $user];

            $msg = "Sehr geehrte Damen und Herren,\n\n";
            $msg .= "fuer Sie bzw. Ihre Institution wurden im Online Profildienst des GBV bereitgestellt: \n";
            $msg .= "\n";
            $msg .= ">\t" . $r['titles'] . " Titel\n";
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

    public function sendReportMail($stats) {

        $fails = 0;
        $total = 0;

        foreach(array_values($stats) as $stat){
            $fails += $stat['fails'];
            $total += $stat['total'];
        }

        if ($total > 0 || $fails > 0) {

            $msg = "Summary: \n";
            $msg .= "Imported titles: " . Util::getFormattedStat($stats, 'import-titles', 'total') . " (failed: " . Util::getFormattedStat($stats, 'import-titles', 'fails') . ")\n";
            $msg .= "Updated titles: " . Util::getFormattedStat($stats, 'update-titles', 'total') . " (failed: " . Util::getFormattedStat($stats, 'update-titles', 'fails') . ")\n";
            $msg .= "\n";
            $msg .= "Imported users: " . Util::getFormattedStat($stats, 'import-users', 'total') . " (failed: " . Util::getFormattedStat($stats, 'import-users', 'fails') . ")\n";
            $msg .= "Updated users: " . Util::getFormattedStat($stats, 'update-users', 'total') . " (failed: " . Util::getFormattedStat($stats, 'update-users', 'fails') . ")\n";
            $msg .= "\n";
            // TODO
            $msg .= "Covers checked: " . 0 . " (without a cover: " . 0 . ")\n";


            $mail = new PHPMailer();

            $mail->From = 'import@online-profildienst.gbv.de';
            $mail->FromName = 'Profildienst Import';

            $emails = $this->config->getValue('logging', 'mail');
            foreach ($emails as $email) {
                $mail->addAddress($email);
            }

            if (filesize($this->logger->getLogPath()) < $this->config->getValue('logging', 'max_mailsize')) {
                $mail->addAttachment($this->logger->getLogPath(), 'Log.log');
            } else {
                $msg .= "\n";
                $msg .= "A log file has been generated, but it is too big to be attached.";
                $msg .= "It can be found here: " . $this->logger->getLogPath() . "\n";
            }


            $mail->Subject = 'Import finished!';
            $mail->Body = $msg;

            if (!$mail->send()) {
                $this->log->addError('Message could not be sent.');
                $this->log->addError($mail->ErrorInfo);
            } else {
                $this->log->addInfo('Message has been sent');
            }
        } else {
            $this->log->addInfo('No email sent since nothing happened.');
        }
    }

    public function sendErrorMail($errors) {
        // TODO
    }

}