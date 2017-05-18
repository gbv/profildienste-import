<?php


namespace Commands;


use Config\Config;
use Services\LogService;
use Importer\RemoteFetcher;
use Services\MailerService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoteFetcherCommand extends BaseImportCommand {

    private $remoteFetcher;
    private $config;

    public function __construct(LogService $logService, MailerService $mailerService, RemoteFetcher $remoteFetcher, Config $config) {
        parent::__construct($logService, $mailerService);
        $this->remoteFetcher = $remoteFetcher;
        $this->config = $config;
    }

    protected function configure() {
        parent::configure();
        $this->setName('import:remote')
            ->setDescription('Starts the fetching of remote titles.');
    }


    protected function executeImport(InputInterface $input, OutputInterface $output) {

        if(!$this->config->getValue('remote', 'enable')){
            $this->logService->getLog()->addInfo('Remote fetching is disabled.');
            return;
        }

        $this->remoteFetcher->run();
    }
}