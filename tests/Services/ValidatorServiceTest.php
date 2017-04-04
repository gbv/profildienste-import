<?php

namespace Services;

class ValidatorServiceTest extends \BaseTest {

    /**
     * @var ValidatorService
     */
    private $validatorService;

    public function setUp() {
        parent::setUp();
        $this->validatorService = $this->container['validatorService'];
    }

    public function testDirectoryCreation() {

        foreach ($this->validatorService->getRequiredDirs() as $dir) {
            $this->assertFalse($this->rootDir->hasChild($dir));
        }

        $ret = $this->validatorService->createLocalDirs();

        $dirs = array_map(function ($dir) {
            return $dir->url();
        }, $this->rootDir->getChildren());

        $this->assertTrue(count(array_diff($dirs, $this->validatorService->getRequiredDirs())) === 0);

        $this->assertTrue($ret);
    }

}
