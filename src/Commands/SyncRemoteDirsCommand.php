<?php

namespace Commands;


use Exception;
use Config\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class SyncRemoteDirsCommand extends Command {

    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config) {
        parent::__construct();
        $this->config = $config;
    }

    protected function configure() {
        $this->setName('config:sync')
            ->setDescription('Syncs the local configuration with the remote dirs')
            ->setHelp('Shows all differences between the actual remote dirs and the local config and updates the config.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $io = new SymfonyStyle($input, $output);

        $io->title('Remote Directories Syncing Tool');

        // this tool is only useful when the remote feature is enabled
        if (!$this->config->getValue('remote', 'enable')) {
            $io->error('Syncing is not available because the remote feature has been disabled.');
            return;
        }

        $output->writeln('<info>Fetching a list with all export directories from the remote server...</info>');

        // construct the ssh command to list all export dirs we're interested in
        $cmd = sprintf('ssh %s@%s \'cd %s; find `pwd` -maxdepth 2 -mindepth 2 -name "export"\'',
            $this->config->getValue('remote', 'user'),
            $this->config->getValue('remote', 'host'),
            $this->config->getValue('remote', 'base')
        );

        // execute the command
        exec($cmd, $cmdOutput, $ret);

        if ($ret !== 0) {
            $io->error('Failed to fetch a list with the remote dirs. Please see the output below for details.');
            $io->error($cmdOutput);
            return;
        }

        $remoteDirs = $cmdOutput;
        $localDirs = $this->config->getValue('remote', 'dirs');

        $dirsOnRemoteNotLocal = array_diff($remoteDirs, $localDirs);
        $dirsLocalNotOnRemote = array_diff($localDirs, $remoteDirs);

        $io->writeln('');
        $io->section('Stats');
        $io->writeln("<info>Currently configured:</info>\t<options=bold>" . count($localDirs) . "</>");
        $io->writeln("<info>Directories to add:</info>\t<fg=green;options=bold>" . count($dirsOnRemoteNotLocal) . "</>");
        $io->writeln("<info>Directories to remove:</info>\t<fg=red;options=bold>" . count($dirsLocalNotOnRemote) . "</>");
        $io->writeln('');

        if (count($dirsOnRemoteNotLocal) > 0) {
            $this->handleAddingDirs($input, $output, $dirsOnRemoteNotLocal);
        }

        if (count($dirsLocalNotOnRemote) > 0) {
            $this->handleRemovingDirs($input, $output, $dirsLocalNotOnRemote);
        }

        if (count($dirsOnRemoteNotLocal) === 0 && count($dirsLocalNotOnRemote) === 0) {
            $io->writeln('<info>The list of configured dirs is up-to-date with the remote dirs!</info>');
            return;
        }
    }

    private function handleAddingDirs(InputInterface $input, OutputInterface $output, array $dirs) {

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            '<info>Please select the dirs you want to <options=bold>add</> to the configuration (default: all listed dirs)</info>',
            $dirs,
            join(',', range(0, count($dirs) - 1))
        );
        $question->setMultiselect(true);

        $selected = $helper->ask($input, $output, $question);

        foreach ($selected as $dir) {
            $this->config->addRemoteDir($dir);
        }

        $output->writeln('<info>Successfully added <options=bold>' . count($selected) . '</> dirs to the config!</info>');
        $output->writeln('');
    }

    private function handleRemovingDirs(InputInterface $input, OutputInterface $output, array $dirs) {

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            '<info>Please select the dirs you want to <options=bold>remove</> to the configuration (default: all listed dirs)</info>',
            $dirs,
            join(',', range(0, count($dirs) - 1))
        );
        $question->setMultiselect(true);

        $selected = $helper->ask($input, $output, $question);

        foreach ($selected as $dir) {
            try {
                $this->config->removeRemoteDir($dir);
            } catch (Exception $e) {
                $output->writeln('<error>' . $e . '</error>');
            }
        }

        $output->writeln('<info>Successfully removed <options=bold>' . count($selected) . '</> dirs from the config!</info>');
        $output->writeln('');
    }

}