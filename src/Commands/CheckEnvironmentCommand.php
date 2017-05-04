<?php
/**
 * Created by PhpStorm.
 * User: luca
 * Date: 07.04.17
 * Time: 02:23
 */

namespace Commands;


use Config\CheckResult;
use Config\Config;
use Services\ValidatorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckEnvironmentCommand extends Command {

    private $validatorService;
    private $config;

    public function __construct(ValidatorService $validatorService, Config $config) {
        parent::__construct();
        $this->validatorService = $validatorService;
        $this->config = $config;
    }

    protected function configure() {
        $this->setName('config:check')
            ->setDescription('Checks if the config is valid and the environment properly set up')
            ->setHelp('Checks if everything is good to go. These checks are also automatically performed before each import.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $output->writeln('<info>Performing the configuration and environment check...</info>');

        $result = $this->validatorService->checkEnvironment();

        if ($result->getStatus() !== CheckResult::PASSED) {
            $output->writeln('<error>The configuration contains errors or the environment is not properly set up:</error>');
            $output->writeln($result->getErrors());
            $output->writeln('<info>Please fix these errors and run the check again.</info>');
            return -1;
        }

        if ($this->config->getValue('firstrun')) {
            $this->config->firstRunCompleted();
        }

        $output->writeln('<info>The configuration is valid and the environment is set up correctly!</info>');
        return 0;
    }

}