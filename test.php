<?php

require 'vendor/autoload.php';

use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;

$loader = new Twig_Loader_Filesystem('resources/templates');
$twig = new Twig_Environment($loader);

$template = $twig->load('reportMail.twig');

$mail = new Message;
$mail->setFrom('Profildienst Import <import@online-profildienst.gbv.de>')
    ->addTo('peter@example.com')
    ->setSubject('Neue Titel im Online Profildienst')
    ->setHtmlBody($template->render([
        'id' => '9706',
        'titles' => 10
    ]));

$mailer = new SendmailMailer;
$mailer->send($mail);