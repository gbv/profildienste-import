<?php
use PHPUnit\Framework\TestCase;

require 'bootstrap/init.php';

abstract class BaseTest extends TestCase {

    protected $container;

    public function __construct(){
        parent::__construct();
        $this->container = new Pimple\Container();
        initContainer($this->container);
        $this->container['log']->setQuiet();
    }

}