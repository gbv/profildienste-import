<?php

namespace Config;


use Exception;

class ConfigTest extends \BaseTest {

    /**
     * @var Config
     */
    private $config;

    public function __construct() {
        parent::__construct();
        $this->config = $this->container['config'];
    }

    public function testGetConfigFilePath() {
        $expectedPath = Config::getBaseDir() . DIRECTORY_SEPARATOR . Config::$CONFIG_FILENAME;
        $this->assertEquals($expectedPath, Config::getConfigFilePath());
    }

    public function testSetFirstRunCompleted() {
        $this->assertTrue($this->config->getValue('firstrun'));
        $this->config->firstRunCompleted();
        $this->assertFalse($this->config->getValue('firstrun'));
    }

    public function testGetValue() {
        $this->assertNotNull($this->config->getValue('database', 'host'));
        $this->assertTrue(is_string($this->config->getValue('database', 'host')));

        $pathWithTrailingSlash = $this->config->getValue('logging', 'dir', true);
        $this->assertStringEndsWith(DIRECTORY_SEPARATOR, $pathWithTrailingSlash);

        $dbCat = $this->config->getValue('database');
        $this->assertTrue(is_array($dbCat));
        $this->assertArrayHasKey('host', $dbCat);
        $this->assertArrayHasKey('port', $dbCat);

        $this->expectException(Exception::class);
        $this->config->getValue('foo');

        $this->expectException(Exception::class);
        $this->config->getValue('database', 'foo');
    }

    public function testGetTitlesDir() {
        $dir = $this->config->getTitlesDir();
        $this->assertDirectoryExists($dir);
    }

    public function testGetTitlesFailDir() {
        $dir = $this->config->getTitlesFailDir();
        $this->assertDirectoryExists($dir);
    }

    public function testGetUsersDir() {
        $dir = $this->config->getUsersDir();
        $this->assertDirectoryExists($dir);
    }

    public function testGetUsersFailDir() {
        $dir = $this->config->getUsersFailDir();
        $this->assertDirectoryExists($dir);
    }
}
