<?php

namespace Util;

use Config\Config;
use Exception;

class Validator {

    private $config;
    private $database;
    private $log;
    private $mailer;

    public function __construct(Config $config, Database $database, Log $log, Mailer $mailer) {
        $this->config = $config;
        $this->database = $database;
        $this->log = $log;
        $this->mailer = $mailer;
    }

    public function checkEnvironment() {

        $errorsOccurred = false;

        $firstRun = $this->config->getValue('firstrun');
        if ($firstRun) {
            $this->log->setVerbose();
        }

        $log = $this->log->getLog();

        $log->addInfo('Trying to connect to the database...');
        try {
            $this->database->checkConnectivity();
            $log->addInfo('Connected!');
        } catch (Exception $e) {
            $log->addError('Fail! Reason: ' . $e->getMessage());
            $errorsOccurred = true;
        }

        if ($this->config->getValue('remote', 'enable')) {
            //check rsync
            $log->addInfo('Checking if rsync is installed...');
            exec('rsync --version 2>&1', $output, $ret);

            if ($ret == 0) {
                $log->addInfo('Yes! (Version: ' . $output[0] . ')');
            } else {
                $log->addError('No. Please install it.');
                $errorsOccurred = true;
            }

            unset($output);
            unset($ret);

            //check ssh
            $log->addInfo('Checking if ssh is installed...');
            exec('ssh -V 2>&1', $output, $ret);

            if ($ret == 0) {
                $log->addInfo('Yes! (Version: ' . $output[0] . ')');
            } else {
                $log->addError('No. Please install it.');
                $errorsOccurred = true;
            }

            unset($output);
            unset($ret);

            //try to connect
            $log->addInfo('Trying to connect to the remote server and checking if remote directories exist...');
            $dirs = $this->config->getValue('remote', 'dirs');
            foreach ($dirs as $dir) {

                $dir = Util::addTrailingSlash($dir);

                exec('ssh ' . $this->config->getValue('remote', 'user') . '@' . $this->config->getValue('remote', 'host') . ' stat ' . $dir . ' 2>&1', $output, $ret);

                if ($ret == 0) {
                    $log->addInfo("\t> " . $dir . ": OK! \n");
                } else {
                    $log->addError("\t> " . $dir . ": FAIL! An error occured: \n");
                    foreach ($output as $line) {
                        $log->addError("\t\t> " . $line . "\n");
                    }
                    $log->addError("\nPlease check your configuration and the remote server.\n");
                    $errorsOccurred = true;
                }
            }
        }

        //check if all dirs exist
        $dirsToCheck = [
            $this->config->getValue('dirs', 'title_import'),
            $this->config->getValue('dirs', 'title_update'),
            $this->config->getValue('dirs', 'user_import'),
            $this->config->getValue('dirs', 'user_update'),
            $this->config->getValue('dirs', 'temp'),
            $this->config->getValue('dirs', 'temp', true) . 'fail/',
            $this->config->getValue('logging', 'dir')
        ];

        $log->addInfo("Checking if all local directories exist and create them if possible.");
        if ($firstRun) {
            foreach ($dirsToCheck as $dir) {
                $logMessage = "Checking " . $dir . "... ";
                $checkResult = Util::checkAndCreateDir($dir);
                if (is_null($checkResult)) {
                    $logMessage.= "Exists!";
                    $log->addInfo($logMessage);
                } else if ($checkResult) {
                    $logMessage.= "Created!";
                    $log->addInfo($logMessage);
                } else {
                    $logMessage.= "Create failed!";
                    $log->addError($logMessage);
                    $errorsOccurred = true;
                }
            }
        } else {
            foreach ($dirsToCheck as $dir) {
                $logMessage = "Checking " . $dir . "... ";
                if (is_dir($dir)) {
                    $logMessage.= "Exists!";
                    $log->addInfo($logMessage);
                } else {
                    $logMessage.= "Missing or not a directory!";
                    $log->addError($logMessage);
                    $errorsOccurred = true;
                }
            }
        }

        // check if the mail is valid
        $validAddresses = [];
        if ($this->config->getValue('logging', 'enable_mail')) {
            $log->addInfo("Checking if the log emails are valid...");
            $emails = $this->config->getValue('logging', 'mail');
            foreach ($emails as $email) {
                $logMessage = "\t " . $email . " ... ";
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $logMessage.= "Valid!";
                    $log->addInfo($logMessage);
                    $validAddresses[] = $email;
                } else {
                    $logMessage.= "Invalid!";
                    $log->addError($logMessage);
                    $errorsOccurred = true;
                }
            }
        }

        // cover check
        // TODO: Cover Check

        if ($errorsOccurred) {

            $errors = $this->log->getLoggedErrors();

            // show all errors in a readable list
            $consoleErrorList = join("\n", array_map(function ($r) {
                return "\t * ".$r;
            }, $errors));

            // try to send a mail if there was at least one valid email address
            if ($this->config->getValue('logging', 'enable_mail') && count($validAddresses) > 0) {
                $this->mailer->sendErrorMail($errors);
            }

            throw new Exception("The environment is not set up properly. Please fix the following errors to use the importer: \n".$consoleErrorList);
        }

        //disable check on next run
        if ($firstRun) {
            $this->config->setFirstRun();
            fprintf(STDOUT, "The environment is properly set up, you can use the importer now.\nRun the importer again to start an import.");
            exit(0);
        }
    }
}