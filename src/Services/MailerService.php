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

    public function sendUserNotificationMails(){

        if (!$this->config->getValue('mailer', 'enable')) {
            // notification mailing feature is disabled in this case
            return;
        }

        $tStats = $this->statsService->getTitleStats();

        if (count(array_keys($tStats)) > 0) {

            $mapping = $this->config->getValue('mailer', 'mapping');

            $mailer = new SendmailMailer;
            $template = $this->twig->load('templates/notificationMail.twig');

            $filteredStats = array_filter($tStats, function ($stat) use ($mapping){
                return isset($mapping[$stat]);
            }, ARRAY_FILTER_USE_KEY);

            foreach ($filteredStats as $user => $titles) {

                $mail = new Message;
                $mail->setFrom('Profildienst Import <import@online-profildienst.gbv.de>')
                    ->setSubject('Neuen Daten im Online Profildienst')
                    ->setHtmlBody($template->render([
                        'titles' => $titles,
                        'id' => $user
                    ]));

                foreach ($mapping[$user] as $email) {
                    $mail->addTo($email);
                }

                $mailer->send($mail);
                $this->log->addInfo('Notification e-mail sent for user '.$user);
            }
        }
    }
    
    public function sendReportMail() {

        $stats = $this->statsService->getStats();

        $total = array_reduce(array_values($stats), function ($carry, $stat){
            return $carry + $stat['total'];
        }, 0);

        if ($total > 0) {

            $failed = array_reduce(array_values($stats), function ($carry, $stat){
                return $carry + $stat['failed'];
            }, 0);

            $template = $this->twig->load('templates/reportMail.twig');

            $mail = new Message;
            $mail->setFrom('Profildienst Import <import@online-profildienst.gbv.de>')
                ->setSubject('Profildienst import report')
                ->setHtmlBody($template->render([
                    'failedTitles' => $failed,
                    'stepList' => array_keys($stats),
                    'stats' => $stats
                ]));

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