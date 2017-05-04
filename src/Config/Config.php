<?php

namespace Config;

use Exception;
use Util\Util;

/**
 * Class Config
 *
 * Represents the configuration for the importer. Upon construction, the config
 * will attempt to load a config from the specified baseDir. If no specific dir
 * was specified, the current working directory will be used to search for the
 * config file. If none is present, the default configuration will be used, in
 * case there is a config file, its content will be loaded and merged with the
 * default config.
 *
 * @package Config
 */
class Config {

    /**
     * @var string The filename for the configuration file
     */
    public static $CONFIG_FILENAME = 'config.json';

    /**
     * @var array The actual config
     */
    private $config;

    /**
     * @var array|null If the config was loaded from a file, the loaded config will be stored here, null otherwise.
     */
    private $configFromFile;

    /**
     * @var string Contains the path to the temporary directory created for the run
     */
    private $tempPath;

    /**
     * @var string The base dir for the configuration file
     */
    private static $baseDir;

    /**
     * Config constructor.
     * @throws Exception if a loaded config is invalid
     */
    public function __construct() {

        $this->config = self::getDefaultConfig();

        // If there is a config file, try to load it and merge with the default configuration
        if (file_exists(self::getConfigFilePath())) {

            $this->configFromFile = json_decode(file_get_contents(self::getConfigFilePath()), true);

            if (is_null($this->config)) {
                throw new Exception("Invalid configuration JSON file. Please check the configuration.\n");
            }

            $this->config = Util::array_merge_recursive_distinct($this->config, $this->configFromFile);
        }
    }

    /**
     * Returns the default configuration.
     *
     * @return array
     */
    public static function getDefaultConfig() {
        return [
            'remote' => [
                'enable' => true,
                'host' => 'cbs4.gbv.de',
                'user' => 'cbs_ifd',
                'base' => '/export/home/cbs_ifd/andreas/profildienst/',
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
                'name' => 'pd',
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

    /**
     * Gets a config value from the configuration
     *
     * @param $category string The category in the configuration structure
     * @param null|string $key Optional key in the specified category
     * @param bool $checkForTrailingSlash In case of paths, trailing slashes may be appended automatically
     * @return mixed|string The config entry
     * @throws Exception if no such category or key in the category exists
     */
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

    /**
     * Stores to the config that the first run of the importer has been
     * completed successfully.
     *
     * @throws Exception
     */
    public function firstRunCompleted() {
        $this->config['firstrun'] = false;
        $this->persist();
    }

    private function getTempSaveDir($checkForTrailingSlash = false) {
        if (is_null($this->tempPath)) {
            $this->tempPath = $this->getValue('dirs', 'temp', true) . date('dmY_His');
        }
        return $checkForTrailingSlash ? Util::addTrailingSlash($this->tempPath) : $this->tempPath;
    }

    public function getTitlesDir() {
        return Util::createDir($this->getTempSaveDir(true) . 'titles');
    }

    public function getTitlesFailDir() {
        return Util::createDir($this->getTitlesDir() . 'fail');
    }

    public function getUsersDir() {
        return Util::createDir($this->getTempSaveDir(true) . 'users');
    }

    public function getUsersFailDir() {
        return Util::createDir($this->getUsersDir() . 'fail');
    }

    public static function setBaseDir($dir) {
        self::$baseDir = $dir;
    }

    public static function getBaseDir() {
        return self::$baseDir ?? getcwd();
    }

    public static function getConfigFilePath() {
        return self::getBaseDir() . DIRECTORY_SEPARATOR . self::$CONFIG_FILENAME;
    }

    public static function createConfigFile() {

        if (file_exists(self::getConfigFilePath())) {
            throw new Exception('A configuration file exists already');
        }

        if (is_null(self::getBaseDir()) || !is_dir(self::getBaseDir())) {
            throw new Exception('Invalid config base path');
        }

        // try to write the configuration file
        if (file_put_contents(self::getConfigFilePath(), json_encode(self::getDefaultConfig(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
            throw new Exception("Couldn't create the config file. Please make sure you have sufficient rights to write in this directory.");
        }

        return true;
    }

    /**
     * Adds a directory to the list of remote dirs
     *
     * @param $dir string Directory to add
     * @throws Exception
     */
    public function addRemoteDir($dir) {

        if (!$this->getValue('remote', 'enable')) {
            throw new Exception('The remote fetching feature is disabled!');
        }

        if (is_null($dir) || !is_string($dir)) {
            throw new Exception('The provided dir is invalid!');
        }

        $this->config['remote']['dirs'][] = $dir;
        $this->persist();
    }

    /**
     * Removes a directory to the list of remote dirs
     *
     * @param $dir string Directory to add
     * @throws Exception
     */
    public function removeRemoteDir($dir) {

        if (!$this->getValue('remote', 'enable')) {
            throw new Exception('The remote fetching feature is disabled!');
        }

        if (is_null($dir) || !is_string($dir)) {
            throw new Exception('The provided dir is invalid!');
        }

        $index = array_search($dir, $this->config['remote']['dirs']);

        if ($index === false) {
            throw new Exception('The specified directory does not exist in the config');
        }

        array_splice($this->config['remote']['dirs'],$index, 1);
        $this->persist();
    }

    /**
     * This function will persist the current configuration if the initial configuration was loaded from a file.
     *
     * @throws Exception
     */
    private function persist() {
        if (!is_null($this->configFromFile)) {
            if (file_put_contents(self::getConfigFilePath(), json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                throw new Exception('Couldn\'t write to config file!');
            }
        }
    }

}