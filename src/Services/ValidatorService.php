<?php

namespace Services;

use Config\Config;
use Exception;
use Util\Util;

class ValidatorService {

    private $config;
    private $database;
    private $logService;
    private $mailer;

    private $log;
    private $validLogMails;

    public function __construct(Config $config, DatabaseService $databaseService, LogService $logService, MailerService $mailerService) {
        $this->config = $config;
        $this->database = $databaseService;
        $this->logService = $logService;
        $this->mailer = $mailerService;

        $this->log = $this->logService->getLog();
        $this->validLogMails = [];
    }

    public function checkEnvironment() {

        $errorsOccurred = false;

        // If this is the first run of the importer, always show errors on console
        // for easier debugging.
        $firstRun = $this->config->getValue('firstrun');
        if ($firstRun) {
            $this->logService->setVerbose();
        }

        $errorsOccurred = !$this->checkDatabaseConnectivity() || $errorsOccurred;

        // the following checks are only performed when remote fetching is enabled
        if ($this->config->getValue('remote', 'enable')) {
            $errorsOccurred = !$this->checkRsyncIsPresent() || $errorsOccurred;
            $errorsOccurred = !$this->checkSSHIsPresent() || $errorsOccurred;
            $errorsOccurred = !$this->checkRemoteHost() || $errorsOccurred;
        }

        //check if all dirs exist
        if ($firstRun) {
            $errorsOccurred = !$this->createLocalDirs() || $errorsOccurred;
        } else {
            $errorsOccurred = !$this->checkLocalDirs() || $errorsOccurred;
        }

        // if the log mailing feature is enabled, check that all log mail addresses are valid
        if ($this->config->getValue('logging', 'enable_mail')) {
            $errorsOccurred = !$this->checkLogMailAddresses() || $errorsOccurred;
        }

        // if the mailing feature is enabled, check that all mail addresses are valid
        if ($this->config->getValue('mailer', 'enable')) {
            $errorsOccurred = !$this->checkMailingFeatureAddresses() || $errorsOccurred;
        }

        // cover check
        // TODO: Cover Check

        if ($errorsOccurred) {

            $errors = $this->logService->getLoggedErrors();

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
    }

    /**
     * Checks if the importer is able to connect to the database.
     *
     * @return bool true if the database can be accessed
     */
    private function checkDatabaseConnectivity() {
        $this->log->addInfo('Trying to connect to the database...');
        try {
            $this->database->checkConnectivity();
            $this->log->addInfo('Connected!');
        } catch (Exception $e) {
            $this->log->addError('Fail! Reason: ' . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Checks if rsync is installed on the system.
     *
     * @return bool true if rsync is installed,
     */
    private function checkRsyncIsPresent() {
        $this->log->addInfo('Checking if rsync is installed...');
        exec('rsync --version 2>&1', $output, $ret);

        if ($ret === 0) {
            $this->log->addInfo('Yes! (Version: ' . $output[0] . ')');
        } else {
            $this->log->addError('No. Please install it.');
            return false;
        }
        return true;
    }

    /**
     * Checks if ssh is installed on the system.
     *
     * @return bool true if ssh is installed
     */
    private function checkSSHIsPresent() {
        $this->log->addInfo('Checking if ssh is installed...');
        exec('ssh -V 2>&1', $output, $ret);

        if ($ret === 0) {
            $this->log->addInfo('Yes! (Version: ' . $output[0] . ')');
        } else {
            $this->log->addError('No. Please install it.');
            return false;
        }
        return true;
    }

    /**
     * Connects to the specified remote host and checks if all in the configuration specified directories exist.
     *
     * @return bool true if everything is set up properly
     */
    private function checkRemoteHost() {

        $errorsOccurred = false;

        $this->log->addInfo('Trying to connect to the remote server and checking if remote directories exist...');
        $dirs = $this->config->getValue('remote', 'dirs');
        foreach ($dirs as $dir) {

            $dir = Util::addTrailingSlash($dir);

            exec('ssh ' . $this->config->getValue('remote', 'user') . '@' . $this->config->getValue('remote', 'host') . ' stat ' . $dir . ' 2>&1', $output, $ret);

            if ($ret == 0) {
                $this->log->addInfo("\t> " . $dir . ": OK! \n");
            } else {
                $this->log->addError("\t> " . $dir . ": FAIL! An error occured: \n");
                foreach ($output as $line) {
                    $this->log->addError("\t\t> " . $line . "\n");
                }
                $this->log->addError("\nPlease check your configuration and the remote server.\n");
                $errorsOccurred = true;
            }
        }

        return !$errorsOccurred;
    }

    /**
     * Returns an array with all required local directories.
     * These dirs must exist so that the importer can function properly.
     *
     * @return array
     */
    public function getRequiredDirs() {
        return [
            $this->config->getValue('dirs', 'title_import'),
            $this->config->getValue('dirs', 'title_update'),
            $this->config->getValue('dirs', 'user_import'),
            $this->config->getValue('dirs', 'user_update'),
            $this->config->getValue('dirs', 'temp'),
            $this->config->getValue('logging', 'dir')
        ];
    }

    /**
     * Checks if the dirs specified in @see getRequiredDirs() exist. If a directory
     * does not exist (as expected in the first run of the importer), it will be created.
     *
     * @return bool Returns true, if all dirs existed or could be created.
     */
    public function createLocalDirs() {
        $this->log->addInfo("Checking if all local directories exist and create them if possible.");
        $errorsOccurred = false;
        foreach ($this->getRequiredDirs() as $dir) {
            $logMessage = "Checking " . $dir . "... ";
            $checkResult = Util::checkAndCreateDir($dir);
            if (is_null($checkResult)) {
                $logMessage .= "Exists!";
                $this->log->addInfo($logMessage);
            } else if ($checkResult) {
                $logMessage .= "Created!";
                $this->log->addInfo($logMessage);
            } else {
                $logMessage .= "Create failed!";
                $this->log->addError($logMessage);
                $errorsOccurred = true;
            }
        }
        return !$errorsOccurred;
    }

    /**
     * Checks if the dirs specified in @see getRequiredDirs() exist,
     * but will not create them if they don't exist.
     *
     * @return bool true if all exist
     */
    public function checkLocalDirs() {
        foreach ($this->getRequiredDirs() as $dir) {
            $logMessage = "Checking " . $dir . "... ";
            if (is_dir($dir)) {
                $logMessage .= "Exists!";
                $this->log->addInfo($logMessage);
            } else {
                $logMessage .= "Missing or not a directory!";
                $this->log->addError($logMessage);
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if all email addresses for the log mailing are valid.
     *
     * @return bool true if all are valid.
     */
    public function checkLogMailAddresses () {
        $errorsOccurred = false;
        $this->log->addInfo("Checking if the log emails are valid...");
        $emails = $this->config->getValue('logging', 'mail');
        foreach ($emails as $email) {
            $logMessage = "\t " . $email . " ... ";
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $logMessage .= "Valid!";
                $this->log->addInfo($logMessage);
                $this->validLogMails[] = $email;
            } else {
                $logMessage .= "Invalid!";
                $this->log->addError($logMessage);
                $errorsOccurred = true;
            }
        }
        return !$errorsOccurred;
    }

    /**
     * Checks if all email addresses used by the mailing feature are valid.
     *
     * @return bool true if all are valid.
     */
    public function checkMailingFeatureAddresses() {
        $errorsOccurred = false;
        $mappingAddresses = array_values($this->config->getValue('mailer', 'mapping'));

        $this->log->addInfo("Checking if all email addresses used by the mailing feature are valid...");
        foreach ($mappingAddresses as $addresses) {
            foreach ($addresses as $address) {
                if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
                    $this->log->addError($address.' is invalid!');
                    $errorsOccurred = true;
                }
            }
        }

        if (!$errorsOccurred) {
            $this->log->addInfo('All addresses are valid!');
        }

        return !$errorsOccurred;
    }
}