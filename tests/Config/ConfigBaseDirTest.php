<?php

namespace Config;

use org\bovigo\vfs\vfsStream;

class ConfigBaseDirTest extends \BaseTest {

    public function setUp() {
    }

    public function testDefaultBaseDir() {
        Config::setBaseDir(null);
        $this->assertEquals(getcwd(), Config::getBaseDir());
    }

    public function testSetBaseDir() {
        $baseDir = vfsStream::setup('baseDir');
        $this->assertNotEquals($baseDir->url(), Config::getBaseDir());
        Config::setBaseDir($baseDir->url());
        $this->assertEquals($baseDir->url(), Config::getBaseDir());
    }

}
