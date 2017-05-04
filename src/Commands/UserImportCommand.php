<?php

namespace Commands;


use Importer\UserImporter;
use Services\LogService;
use Services\MailerService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserImportCommand extends BaseImportCommand {

    private $userImporter;

    public function __construct(LogService $logService, MailerService $mailerService, UserImporter $userImporter) {
        parent::__construct($logService, $mailerService);
        $this->userImporter = $userImporter;
    }

    protected function configure() {
        parent::configure();
        $this->setName('import:users')
            ->setDescription('Starts the user data import.');
    }

    protected function executeImport(InputInterface $input, OutputInterface $output) {
        $this->userImporter->run();
    }

}