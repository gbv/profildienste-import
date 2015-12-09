<?php

namespace Util;

class Config {

    private static $instance;
    private $config;

    private $temp_path;

    private function __construct(){
        // a config file is present, try to load it
        $this->config = json_decode(file_get_contents('config.json'), true);

        if(is_null($this->config)){
            fprintf(STDERR, "Invalid configuration JSON file. Please check the configuration.\n");
            exit(4);
        }
    }

    public static function getInstance(){
        if(is_null(Config::$instance)){
            Config::$instance = new Config();
        }

        return Config::$instance;
    }

    // getter for values of the configuration
    public function getValue($category, $key = NULL, $checkForTrailingSlash = false){

        if(is_null($key)){
            if(!isset($this->config[$category])){
                fprintf(STDERR, "Incomplete configuration. Please check the configuration.\n");
                exit(5);
            }

            return $checkForTrailingSlash ? Util::addTrailingSlash($this->config[$category]) : $this->config[$category];

        }else{
            if(!isset($this->config[$category]) || !isset($this->config[$category][$key])){
                fprintf(STDERR, "Incomplete configuration. Please check the configuration.\n");
                exit(5);
            }

            return $checkForTrailingSlash ? Util::addTrailingSlash($this->config[$category][$key]) : $this->config[$category][$key];
        }
    }

    public function setFirstRun(){
        $this->config['firstrun'] = false;
        file_put_contents('config.json', json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function getTempSaveDir($checkForTrailingSlash = false){
        if(is_null($this->temp_path)){
            $this->temp_path = Config::getInstance()->getValue('dirs', 'temp', true).date('dmY_His');
        }

        return $checkForTrailingSlash ? Util::addTrailingSlash($this->temp_path) : $this->temp_path;
    }

    public function setupTitleTempDir(){
        if(!is_dir($this->getTempSaveDir(true))){
            mkdir(Util::addTrailingSlash($this->temp_path).'/titles/fail', 0777, true);
        }
    }

    public function setupUserTempDir(){
        if(!is_dir($this->getTempSaveDir(true))){
            mkdir(Util::addTrailingSlash($this->temp_path).'/user/fail', 0777, true);
        }
    }
}