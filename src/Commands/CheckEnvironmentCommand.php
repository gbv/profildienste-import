<?php
/**
 * Created by PhpStorm.
 * User: luca
 * Date: 07.04.17
 * Time: 02:23
 */

namespace Commands;


use Services\ValidatorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckEnvironmentCommand extends Command {

    private $validatorService;

    public function __construct(ValidatorService $validatorService) {
        parent::__construct();
        $this->validatorService = $validatorService;
    }

    protected function configure() {
        $this->setName('config:check')
            ->setDescription('Checks if the config is valid and the environment properly set up')
            ->setHelp('Checks if everything is good to go. These checks are also automatically performed before each import.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->validatorService->checkEnvironment();
    }

}