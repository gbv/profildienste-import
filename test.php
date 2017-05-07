<?php

require 'vendor/autoload.php';

use Rebuy\EanIsbn\Converter\Converter;
use Rebuy\EanIsbn\Converter\Isbn10Converter;
use Rebuy\EanIsbn\Identifier\Ean13;
use Rebuy\EanIsbn\Identifier\Isbn10;
use Rebuy\EanIsbn\Parser\Parser;

/*
$loader = new Twig_Loader_Filesystem('resources/templates');
$twig = new Twig_Environment($loader);

$template = $twig->load('reportMail.twig');

$mail = new Message;
$mail->setFrom('Profildienst Import <import@online-profildienst.gbv.de>')
    ->addTo('peter@example.com')
    ->setSubject('Neue Titel im Online Profildienst')
    ->setHtmlBody($template->render([
        'failedTitles' => false,
        'stepList' => ['a', 'b', 'cdef'],
        'stats' => [
            'TestStep' => ['step' => 'TestStep', 'total' => 200, 'failed' => 30]
        ]
    ]));

$mailer = new SendmailMailer;
$mailer->send($mail);*/

$parser = new Parser([new \Rebuy\EanIsbn\Parser\Ean13Parser(), new \Rebuy\EanIsbn\Parser\Isbn10Parser()]);
$identifier = $parser->parse('3837631516');

echo $identifier; // will print '9780091956141'

if (!$identifier instanceof Isbn10) {
$converter = new Converter([
    Ean13::class => [new Isbn10Converter()]
]);
$identifier = $converter->convert($identifier);
}

echo "\n";

echo $identifier; // will print '0091956145'

echo "\n";