<?php

namespace Commands;

use Config\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListMappingsCommand
 *
 * List all mappings from the configuration
 *
 * @package Commands
 */
class ListMappingsCommand extends Command {

    private $config;

    public function __construct(Config $config) {
        parent::__construct();
        $this->config = $config;
    }

    protected function configure() {
        $this->setName('config:list-mappings')
            ->setDescription('Lists all ID <-> E-mail mappings from the configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        if(!$this->config->getValue('mailer', 'enable')) {
            $output->writeln('<error>The mailing feature is disabled. If you want to enable it, set mailer.enable to true in the config.json!</error>');
            return;
        }

        $output->writeln('<info>The following mappings are currently configured:</info>');

        $mappings = $this->config->getValue('mailer', 'mapping');
        $tableData = [];
        foreach ($mappings as $id => $emails) {
            $tableData[] = [$id, join(',', $emails)];
        }

        $table = new Table($output);
        $table->setHeaders(['ID', 'E-mail(s)']);
        $table->setRows($tableData);
        $table->render();
    }
}