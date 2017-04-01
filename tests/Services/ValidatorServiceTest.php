<?php

namespace Services;

use Config\Config;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class ValidatorServiceTest extends \BaseTest {

    /**
     * @var ValidatorService
     */
    private $validatorService;

    public function setUp() {

        $this->validatorService = $this->container['validatorService'];
    }

    public function testDirectoryCreation(){
        foreach ($this->validatorService->getRequiredDirs() as $dir) {
            $this->assertFalse($this->rootDir->hasChild($dir));
        }
        $this->validatorService->createLocalDirs();
        foreach ($this->validatorService->getRequiredDirs() as $dir) {
            echo "$dir \n";
        }
        foreach ($this->validatorService->getRequiredDirs() as $dir) {
            $this->assertTrue($this->rootDir->hasChild($dir));
        }
    }

}
