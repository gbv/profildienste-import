<?php

namespace Commands;

use Config\Config;
use Services\DatabaseService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class ListMappingsCommand
 *
 * Adds a mappings to the configuration
 *
 * @package Commands
 */
class AddMappingCommand extends Command {

    private $config;
    private $databaseService;

    public function __construct(Config $config, DatabaseService $databaseService) {
        parent::__construct();
        $this->config = $config;
        $this->databaseService = $databaseService;
    }

    protected function configure() {
        $this->setName('config:mapping-add')
            ->setDescription('Adds an ID <-> E-mail mapping to the configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        if(!$this->config->getValue('mailer', 'enable')) {
            $output->writeln('<error>The mailing feature is disabled. If you want to enable it, set mailer.enable to true in the config.json!</error>');
            return;
        }

        $helper = $this->getHelper('question');

        // ask for ID
        $userId = '';
        do {
            $question = new Question('<question>Please enter the user ID of the new mapping: </question>', '');
            $userId = $helper->ask($input, $output, $question);

            if (!empty($userId) && !$this->databaseService->userExists($userId)) {
                $confirmQuestion = new ConfirmationQuestion('<comment>A user with ID \''.$userId.
                    '\' does not exist in the user database. Continue anyway? [y/n]</comment>', false);
                if (!$helper->ask($input, $output, $confirmQuestion)) {
                    $userId = '';
                }
            }
        } while (empty($userId));

        $mapping = $this->config->getValue('mailer', 'mapping');

        if (isset($mapping[$userId])) {
            $output->writeln(sprintf('<info>Found a mapping for %s with %d entries</info>',
                $userId, count($mapping[$userId])));
        } else {
            $output->writeln(sprintf('<info>No existing mapping for found, creating a new one</info>', $userId));
        }

        $email = '';
        do {
            $question = new Question(sprintf('<question>Please enter the email for sending notifications to user %s: </question>', $userId), '');
            $email = $helper->ask($input, $output, $question);

            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $output->writeln('<error>You did not enter a valid e-mail address!</error>');
            }

        } while (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL));

        // check if mapping already exists and store it
        if (isset($mapping[$userId]) && array_search($email, $mapping[$userId])) {
            $output->writeln('<comment>This mapping already exists.</comment>');
        } else {
            $this->config->addMapping($userId, $email);
            $output->writeln('<info>Saved new mapping to the configuration!</info>');
        }
    }
}