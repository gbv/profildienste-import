<?php

use Util\Config;
use Util\Database;
use Util\Util;

class Initializer implements Importer{

    private function createConfigFile(){

        // we need that command to determine the absolute paths
        if(getcwd() === FALSE){
            fprintf(STDERR, "Can't get the current working path.\n");
            exit(2);
        }

        // sample configuration written if none is present
        $config = array(
            'remote' => array(
                'enable' => true,
                'host' => 'cbs4.gbv.de',
                'user' => 'cbs_ifd',
                'dirs' => [
                    '/export/home/cbs_ifd/andreas/profildienst/sub_goettingen/export/',
                    '/export/home/cbs_ifd/andreas/profildienst/wildau/export/'
                ]
            ),
            'dirs' => array(
                'title_import' => getcwd().'/title_import',
                'title_update' => getcwd().'/title_update',
                'user_import' => getcwd().'/user_import',
                'user_update' => getcwd().'/user_update',
                'temp' => getcwd().'/temp'
            ),
            'logging' => array(
                'dir' => getcwd().'/log',
                'mail' => array('keidel@gbv.de'),
                'enable_mail' => true,
                'max_mailsize' => 1000000
            ),
            'cover' => array(
                'access' => 'AKIAJ7PKU5XHAWG65IRQ',
                'secret' => '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'
            ),
            'database' => array(
                'host' => 'localhost',
                'port' => '27017',
                'options' => array(
                    'safe'    => true,
                    'fsync'   => true,
                    'timeout' => 10000
                )
            ),
            'mailer' => array(
                'enable' => true,
                'mapping' => array(
                    '000' => array('keidel@gbv.de')
                )
            ),
            'firstrun' => true
        );

        // try to write the configuration file
        if(file_put_contents('config.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === FALSE){
            fprintf(STDERR, "Couldn't create the config file. Please make sure you have sufficient rights to write in this directory.\n");
            exit(3);
        }

        fprintf(STDOUT, "A configuration file template has been copied to %s.\nPlease review the configuration to make sure it can be used.\n", getcwd().'/config.json');
        exit(1);
    }

    private function checkAndCreateDir($path){

        echo "Checking ".$path."... ";

        if(is_dir($path)){
            echo "Exists!\n";
            return;
        }

        if(mkdir($path)){
            echo "Created!\n";
        }else{
            echo "Create failed!\n";
            exit(6);
        }
    }

    public function run(){

        // Try to create a configuration file if non exists so far
        if(!file_exists('config.json')){
            $this->createConfigFile();
        }

        $config = Config::getInstance();

        if($config->getValue('firstrun')){
            echo "This appears to be your first run of the import.\n";
            echo "I will check the configuration and create the directories if the do not already exist.\n\n";

            echo "Checking if the PHP Mongo extension is available... ";
            if(extension_loaded("mongo")){
                echo "Yes!\n\n";
            }else{
                echo "No. Please install it.\n";
                exit(7);
            }

            echo "Trying to connect to the database... ";
            try{
                Database::getInstance();
                echo "Connected! \n\n";
            }catch(Exception $e){
                echo "Fail! \n";
                echo "Reason: ".$e->getMessage()."\n";
                exit(8);
            }

            if($config->getValue('remote', 'enable')){
                //check rsync
                echo "Checking if rsync is installed...";
                exec('rsync --version 2>&1', $output, $ret);

                if($ret == 0){
                    echo "Yes! (Version: ".$output[0].")\n";
                }else{
                    echo "No. Please install it.\n";
                    exit(6);
                }

                unset($output);
                unset($ret);

                //check ssh
                echo "Checking if ssh is installed...";
                exec('ssh -V 2>&1', $output, $ret);

                if($ret == 0){
                    echo "Yes! (Version: ".$output[0].")\n";
                }else{
                    echo "No. Please install it.\n";
                    exit(6);
                }

                unset($output);
                unset($ret);

                //try to connect
                echo "\n\nTrying to connect to the remote server and checking if remote directories exist...\n";
                $dirs = $config->getValue('remote', 'dirs');
                foreach ($dirs as $dir){

                    $dir = Util::addTrailingSlash($dir);

                    exec('ssh '.$config->getValue('remote', 'user').'@'.$config->getValue('remote', 'host').' stat '.$dir.' 2>&1', $output, $ret);

                    if($ret == 0){
                        echo "\t> ".$dir.": OK! \n";
                    }else{
                        echo "\t> ".$dir.": FAIL! An error occured: \n";
                        foreach($output as $line){
                            echo "\t\t> ".$line."\n";
                        }
                        echo "\nPlease check your configuration and the remote server.\n";
                        exit(6);
                    }
                }

                echo "OK! Everything went fine, connecting is possible and the remote directories exist.\n";

            }

            //check if all dirs exist
            echo "\n\nChecking if all local directories exist and create them if possible.\n";
            $this->checkAndCreateDir($config->getValue('dirs', 'title_import'));
            $this->checkAndCreateDir($config->getValue('dirs', 'title_update'));
            $this->checkAndCreateDir($config->getValue('dirs', 'user_import'));
            $this->checkAndCreateDir($config->getValue('dirs', 'user_update'));
            $this->checkAndCreateDir($config->getValue('dirs', 'temp'));
            $this->checkAndCreateDir($config->getValue('dirs', 'temp', true).'fail/');
            $this->checkAndCreateDir($config->getValue('logging', 'dir'));

            // check if the mail is valid
            if($config->getValue('logging', 'enable_mail')){
                echo "\n\nChecking if the log emails are valid...\n";
                $emails = $config->getValue('logging', 'mail');
                foreach($emails as $email){
                    echo "\t ".$email." ... ";
                    if(filter_var($email ,FILTER_VALIDATE_EMAIL)){
                        echo "Valid!\n";
                    }else{
                        echo "Invalid email!\n";
                        exit(6);
                    }
                }
                echo "\n";

            }

            // cover check
            // TODO: Cover Check

            //disable check on next run
            $config->setFirstRun();
            echo "\n\n All checks passed! The import is now ready to be used!\n";
            exit(0);
        }
    }


}