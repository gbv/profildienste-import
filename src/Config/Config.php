<?php

namespace Config;

use Exception;
use Util\Util;

class Config {

    private static $CONFIG_FILENAME = 'config.json';

    private $config;

    private $configFromFile;

    private $temp_path;

    private static $baseDir;

    public function __construct() {

        $this->config = self::getDefaultConfig();

        // If there is a config file, try to load it and merge with the default configuration
        $configPath = self::getBaseDir().DIRECTORY_SEPARATOR.self::$CONFIG_FILENAME;
        if (file_exists($configPath)) {

            $this->configFromFile = json_decode(file_get_contents($configPath), true);

            if (is_null($this->config)) {
                throw new Exception("Invalid configuration JSON file. Please check the configuration.\n");
            }

            $this->config = Util::array_merge_recursive_distinct($this->config, $this->configFromFile);
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
                'title_import' => self::getBaseDir() . '/title_import',
                'title_update' => self::getBaseDir() . '/title_update',
                'user_import' => self::getBaseDir() . '/user_import',
                'user_update' => self::getBaseDir() . '/user_update',
                'temp' => self::getBaseDir() . '/temp'
            ],
            'logging' => [
                'dir' => self::getBaseDir() . '/log',
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

    public function firstRunCompleted() {
        $this->config['firstrun'] = false;
        if (!is_null($this->configFromFile)) {
            if (file_put_contents(self::$CONFIG_FILENAME, json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                throw new Exception('Couldn\'t write to config file!');
            }
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

    public static function setBaseDir($dir) {
        self::$baseDir = $dir;
    }

    public static function getBaseDir() {
        return self::$baseDir ?? getcwd();
    }

}