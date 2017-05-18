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
        $this->container['resourceFolder'] = dirname(__DIR__).DIRECTORY_SEPARATOR.'resources';
        initContainer($this->container);
    }

    public function setUp() {
        $this->rootDir = vfsStream::setup('testRoot');
        Config::setBaseDir(vfsStream::url($this->rootDir->getName()));
        $this->container['logService']->setQuiet();
    }

}