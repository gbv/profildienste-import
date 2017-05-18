<?php
/**
 * Created by PhpStorm.
 * User: luca
 * Date: 18.05.17
 * Time: 12:56
 */

namespace Commands;

use Services\LogService;
use Importer\TitleImporter;
use Services\MailerService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TitleImportCommand extends BaseImportCommand {

    private $titleImporter;

    public function __construct(LogService $logService, MailerService $mailerService, TitleImporter $titleImporter) {
        parent::__construct($logService, $mailerService);
        $this->titleImporter = $titleImporter;
    }

    protected function configure() {
        parent::configure();
        $this->setName('import:titles')
            ->setDescription('Starts the title import.');
    }

    protected function executeImport(InputInterface $input, OutputInterface $output) {
        $this->titleImporter->run();
    }
}