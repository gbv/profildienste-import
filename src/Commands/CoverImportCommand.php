<?php

namespace Commands;


use Importer\CoverImporter;
use Services\LogService;
use Services\MailerService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CoverImportCommand extends BaseImportCommand{

    private $coverImporter;

    public function __construct(LogService $logService, MailerService $mailerService, CoverImporter $coverImporter) {
        parent::__construct($logService, $mailerService);
        $this->coverImporter = $coverImporter;
    }

    protected function configure() {
        parent::configure();
        $this->setName('import:covers')
            ->setDescription('Starts the cover import.');
    }

    protected function executeImport(InputInterface $input, OutputInterface $output) {
        $this->coverImporter->run();
    }
}