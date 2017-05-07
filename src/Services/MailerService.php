<?php

namespace Services;

use Config\Config;
use Monolog\Logger;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Util\Util;

class MailerService {

    private $record = [];

    /**
     * @var LogService
     */
    private $logService;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StatsService
     */
    private $statsService;

    /**
     * @var Logger
     */
    private $log;

    private $twig;

    public function __construct(LogService $logService, Config $config, StatsService $statsService, string $resFolder) {
        $this->logService = $logService;
        $this->config = $config;
        $this->statsService = $statsService;

        $this->log = $this->logService->getLog();

        $loader = new Twig_Loader_Filesystem($resFolder);
        $this->twig = new Twig_Environment($loader);
    }

/*
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
    }*/

    public function sendReportMail() {

        $stats = $this->statsService->getStats();

        $total = array_reduce(array_values($stats), function ($carry, $stat){
            return $carry + $stat['total'];
        }, 0);

        if ($total > 0) {

            $failed = array_reduce(array_values($stats), function ($carry, $stat){
                return $carry + $stat['failed'];
            }, 0);

            /*foreach ($this->statsService->getStats() as $importer => $stats) {
                $msg .= sprintf('%s : Total: %d, Failed: %d)\n', $importer, Util::format($stats['total']), Util::format($stats['failed']));
            }*/
            /*
            $msg .= "Imported titles: " . Util::getFormattedStat($stats, 'import-titles', 'total') . " (failed: " . Util::getFormattedStat($stats, 'import-titles', 'fails') . ")\n";
            $msg .= "Updated titles: " . Util::getFormattedStat($stats, 'update-titles', 'total') . " (failed: " . Util::getFormattedStat($stats, 'update-titles', 'fails') . ")\n";
            $msg .= "\n";
            $msg .= "Imported users: " . Util::getFormattedStat($stats, 'import-users', 'total') . " (failed: " . Util::getFormattedStat($stats, 'import-users', 'fails') . ")\n";
            $msg .= "Updated users: " . Util::getFormattedStat($stats, 'update-users', 'total') . " (failed: " . Util::getFormattedStat($stats, 'update-users', 'fails') . ")\n";
            $msg .= "\n";*/
            // TODO
            //$msg .= "Covers checked: " . 0 . " (without a cover: " . 0 . ")\n";

            $template = $this->twig->load('templates/reportMail.twig');

            $mail = new Message;
            $mail->setFrom('Profildienst Import <import@online-profildienst.gbv.de>')
                ->setSubject('Profildienst import report')
                ->setHtmlBody($template->render([
                    'failedTitles' => $failed,
                    'stepList' => array_keys($stats),
                    'stats' => $stats
                ]));

            var_dump($stats);

            $emails = $this->config->getValue('logging', 'mail');
            foreach ($emails as $email) {
                $mail->addTo($email);
            }

            $mailer = new SendmailMailer;
            $mailer->send($mail);

        } else {
            $this->log->addInfo('No email sent since nothing happened.');
        }
    }

    public function sendErrorMail($addresses, $errors) {
        // TODO
    }

}