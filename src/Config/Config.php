<?php

namespace Config;

use Exception;
use Util\Util;

class Config {

    private static $CONFIG_FILENAME = 'config.json';

    private $config;

    private $temp_path;

    public function __construct(ConfigCreator $creator) {

        // Try to create a configuration file if non exists so far
        if (!file_exists(self::$CONFIG_FILENAME)) {
            $creator->createConfigFile(self::$CONFIG_FILENAME, $this->getDefaultConfig());
        }

        $this->config = json_decode(file_get_contents(self::$CONFIG_FILENAME), true);

        if (is_null($this->config)) {
            throw new Exception("Invalid configuration JSON file. Please check the configuration.\n");
        }


    }

    // sample configuration written if none is present
    private static function getDefaultConfig() {
        return [
            'remote' => [
                'enable' => true,
                'host' => 'cbs4.gbv.de',
                'user' => 'cbs_ifd',
                'dirs' => []
            ],
            'dirs' => [
                'title_import' => getcwd() . '/title_import',
                'title_update' => getcwd() . '/title_update',
                'user_import' => getcwd() . '/user_import',
                'user_update' => getcwd() . '/user_update',
                'temp' => getcwd() . '/temp'
            ],
            'logging' => [
                'dir' => getcwd() . '/log',
                'mail' => ['keidel@gbv.de'],
                'enable_mail' => true,
                'max_mailsize' => 1000000
            ],
            'cover' => [
                'access' => 'AKIAJ7PKU5XHAWG65IRQ',
                'secret' => '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'
            ],
            'database' => [
                'host' => 'localhost',
                'port' => '27017',
            ],
            'mailer' => [
                'enable' => true,
                'mapping' => [
                    '000' => ['keidel@gbv.de']
                ]
            ],
            'firstrun' => true
        ];
    }

    // getter for values of the configuration
    public function getValue($category, $key = null, $checkForTrailingSlash = false) {

        if (is_null($key)) {
            if (!isset($this->config[$category])) {
                throw new Exception("Incomplete configuration. Please check the configuration.\n");
            }

            return $checkForTrailingSlash ? Util::addTrailingSlash($this->config[$category]) : $this->config[$category];

        } else {
            if (!isset($this->config[$category]) || !isset($this->config[$category][$key])) {
                throw new Exception("Incomplete configuration. Please check the configuration.\n");
            }

            return $checkForTrailingSlash ? Util::addTrailingSlash($this->config[$category][$key]) : $this->config[$category][$key];
        }
    }

    public function setFirstRun() {
        $this->config['firstrun'] = false;
        if (file_put_contents(self::$CONFIG_FILENAME, json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
            throw new Exception('Couldn\'t write to config file!');
        }
    }

    private function getTempSaveDir($checkForTrailingSlash = false) {
        if (is_null($this->temp_path)) {
            $this->temp_path = $this->getValue('dirs', 'temp', true) . date('dmY_His');
        }
        return $checkForTrailingSlash ? Util::addTrailingSlash($this->temp_path) : $this->temp_path;
    }

    public function getTitlesDir() {
        return Util::createDir($this->getTempSaveDir(true) . 'titles');
    }

    public function getTitlesFailDir() {
        return Util::createDir($this->getTitlesDir() . 'fail');
    }

}