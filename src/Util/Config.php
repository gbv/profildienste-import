<?php

namespace Util;

class Config {

    private static $instance;
    private $config;

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
}