<?php
use Config\Config;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

require 'bootstrap/init.php';

abstract class BaseTest extends TestCase {

    protected $container;

    /**
     * @var vfsStreamDirectory
     */
    protected $rootDir;

    public function __construct() {
        parent::__construct();
        $this->container = new Pimple\Container();
        initContainer($this->container);
        $this->container['logService']->setQuiet();
    }

    public function setUp() {
        $this->rootDir = vfsStream::setup('testRoot');
        Config::setBaseDir(vfsStream::url($this->rootDir->getName()));
    }

}