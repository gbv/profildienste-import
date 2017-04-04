<?php

namespace Config;

use Exception;
use org\bovigo\vfs\vfsStream;

class ConfigFileTest extends \BaseTest {

    public function testNoConfigFilePresent() {
        $this->assertEquals(Config::getBaseDir(), $this->rootDir->url());
        $this->assertFalse($this->rootDir->hasChild(Config::$CONFIG_FILENAME));
    }

    public function testConfigFileAlreadyExists() {
        $this->assertEquals(Config::getBaseDir(), $this->rootDir->url());
        vfsStream::newFile(Config::$CONFIG_FILENAME)->at($this->rootDir);

        $this->expectException(Exception::class);
        Config::createConfigFile();
    }

    public function testInvalidBasePath() {
        $dirName = uniqid('foo');
        $this->assertDirectoryNotExists($dirName);

        Config::setBaseDir($dirName);

        $this->expectException(Exception::class);
        Config::createConfigFile();
    }

    public function testConfigFileIsWritten() {
        $this->assertEquals(Config::getBaseDir(), $this->rootDir->url());
        $this->assertFalse($this->rootDir->hasChild(Config::$CONFIG_FILENAME));
        Config::createConfigFile();
        $this->assertTrue($this->rootDir->hasChild(Config::$CONFIG_FILENAME));
    }

    public function testConfigFileContainsValidJSON() {
        Config::createConfigFile();
        $this->assertJson(file_get_contents(Config::getConfigFilePath()));
    }

    public function testConfigFileContainsDefaultConfig() {
        Config::createConfigFile();
        $this->assertJsonStringEqualsJsonFile(Config::getConfigFilePath(), json_encode(Config::getDefaultConfig()));
    }

}
