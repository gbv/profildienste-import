<?php

namespace Commands;

use Config\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class DeleteMappingCommand
 *
 * Deletes email addresses and/or entire mappings from the configuration
 *
 * @package Commands
 */
class DeleteMappingCommand extends Command {

    private $config;

    public function __construct(Config $config) {
        parent::__construct();
        $this->config = $config;
    }

    protected function configure() {
        $this->setName('config:mapping-delete')
            ->setDescription('Deletes an email from a mapping or deletes an entire mapping');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        if (!$this->config->getValue('mailer', 'enable')) {
            $output->writeln('<error>The mailing feature is disabled. If you want to enable it, set mailer.enable to true in the config.json!</error>');
            return;
        }

        $mapping = $this->config->getValue('mailer', 'mapping');

        $helper = $this->getHelper('question');

        // ask for ID
        $userId = '';
        do {
            $question = new Question('<question>Please enter the user ID of the new mapping: </question>', '');
            $userId = $helper->ask($input, $output, $question);

            if (!empty($userId) && !isset($mapping[$userId])) {
                $output->writeln('<comment>No mappings exist for this user ID.</comment>');
            }
        } while (empty($userId) || !isset($mapping[$userId]));

        $emailAddresses = $mapping[$userId];

        $question = new ChoiceQuestion(
            '<info>Please select the addresses you want to <options=bold>delete</>:</info>',
            $emailAddresses,
            join(',', range(0, count($emailAddresses) - 1))
        );
        $question->setMultiselect(true);

        $selected = $helper->ask($input, $output, $question);

        if (count($selected) === count($emailAddresses)) {
            //shortcut
            $emailAddresses = [];
        } else {
            $emailAddresses = array_filter($emailAddresses, function($address) use ($selected) {
                return !in_array($address, $selected);
            });
        }

        $this->config->updateMapping($userId, $emailAddresses);

        if (count($emailAddresses) === 0) {
            $output->writeln(sprintf('<info>Deleted entry for user %s</info>', $userId));
        } else {
            $output->writeln(sprintf('<info>Updated entry for user %s</info>', $userId));
        }
    }
}