<?php

use Commando\Command;
use Config\Config;
use Pimple\Container;

class App {

    private $container;

    private $validator;
    private $log;
    private $config;
    private $mailer;

    public function __construct(Container $container) {
        $this->container = $container;

        $this->validator = $container['validatorService'];
        $this->log = $container['logService'];
        $this->config = $container['configService'];
        $this->mailer = $container['mailerService'];
    }

    public function run() {



        /*$cmd = new Command();
        $this->initCommands($cmd);

        //check if there is a config file, otherwise create one first.
        if (!file_exists(Config::getConfigFilePath())) {
            Config::createConfigFile();
            fprintf(STDOUT, "A configuration file template has been copied to %s.\nPlease review the configuration to make sure it can be used.\n", Config::getConfigFilePath());
            return;
        }

        $this->validator->checkEnvironment();

        -----------
        TODO: Pasted here from the end of ValidatorService

                if ($errorsOccurred) {

            $errors =

            // show all errors in a readable list
            $consoleErrorList = join("\n", array_map(function ($r) {
                return "\t * " . $r;
            }, $errors));

            // try to send a mail if there was at least one valid email address
            if ($this->config->getValue('logging', 'enable_mail') && count($this->validLogMails) > 0) {
                $this->mailer->sendErrorMail($this->validLogMails, $errors);
            }

            throw new Exception("The environment is not set up properly. Please fix the following errors to use the importer: \n" . $consoleErrorList);
        }

        if ($firstRun) {
            $this->config->firstRunCompleted();
            fprintf(STDOUT, "The environment is properly set up, you can use the importer now.\nRun the importer again to start an import.\n");
            exit(0);
        }
        --------

        $this->log->addInfo(print_r($cmd->getFlagValues(), true));
        $importers = $this->getSelectedImportingSteps($cmd);

        $stats = [];
        foreach ($importers as $importer) {
            $step = $importer['step'];
            $step->run();
            $stats[$step['id']] = [
                'total' => $step->getTotal(),
                'fails' => $step->getFails()
            ];
        }

        if ($this->config->getValue('mailer', 'enable')) {

            $mapping = $this->config->getValue('mailer', 'mapping');

            foreach ($mapping as $user => $emails) {
                foreach ($emails as $email) {
                    $this->mailer->sendMailTo($user, $email);
                }

            }
        }

        if ($this->config->getValue('logging', 'enable_mail')) {
            $this->mailer->sendReportMail($stats);
        }
    }

    private function getSelectedImportingSteps(Command $cmd) {

        $flagsSet = false;
        foreach ($cmd->getFlagValues() as $flag => $value) {
            if ($flag === 'v') {
                continue;
            }
            $flagsSet = $flagsSet || $value;
        }

        $fullRun = $cmd['full'] || !$flagsSet;

        $importers = [];

        if ($fullRun || $cmd['remote']) {
            $importers[] = [
                'id' => 'remote',
                'step' => new RemoteFetcher($this->container)
            ];
        }

        if ($fullRun || $cmd['import-titles']) {
            $importers[] = [
                'id' => 'import-titles',
                'step' => new TitleImporter($this->container)
            ];
        }

        if ($fullRun || $cmd['update-titles']) {
            $this->container['log']->getLog()->addInfo('Title Updater not implemented so far!');
        }

        if ($fullRun || $cmd['import-users']) {
            $importers[] = [
                'id' => 'import-users',
                'step' => null
            ];
        }

        if ($fullRun || $cmd['update-users']) {
            $importers[] = [
                'id' => 'update-users',
                'step' => $this->container['userUpdater']
            ];
        }

        if ($fullRun || $cmd['covers']) {
            $importers[] = [
                'id' => 'covers',
                'step' => new CoverImporter($this->container)
            ];
        }

        return $importers;
    }

    private function initCommands(Command $cmd) {
        $cmd->flag('full')
            ->description('Starts a full import (Default).')
            ->boolean();

        $cmd->flag('remote')
            ->description('Fetch data from the remote computer')
            ->boolean();

        $cmd->flag('import-titles')
            ->description('Import titles from the import directory')
            ->boolean();

        $cmd->flag('update-titles')
            ->description('Update titles from the title update directory')
            ->boolean();

        $cmd->flag('covers')
            ->description('Import covers')
            ->boolean();

        $cmd->flag('import-users')
            ->description('Import user from the user import directory')
            ->boolean();

        $cmd->flag('update-users')
            ->description('Update user from the user update directory')
            ->boolean();

        $cmd->flag('v')
            ->aka('verbose')
            ->description('Show details')
            ->boolean();*/
    }
}