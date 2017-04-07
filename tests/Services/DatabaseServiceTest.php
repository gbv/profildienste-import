<?php

namespace Services;


use Config\Config;
use Exception;
use MongoDB\Client;
use org\bovigo\vfs\vfsStream;

class DatabaseServiceTest extends \BaseTest {

    /**
     * @var DatabaseService
     */
    private $databaseService;

    public static function setUpBeforeClass() {

        if (empty(getenv('TEST_DB_HOST')) || empty(getenv('TEST_DB_PORT'))
            || empty(getenv('TEST_DB_NAME'))) {
            throw new Exception('Test DB connection information has to be provided in the test configuration');
        }

        $dir = vfsStream::setup('dbTest');
        Config::setBaseDir(vfsStream::url($dir->getName()));
        Config::createConfigFile();
        if (!file_exists(Config::getConfigFilePath())) {
            throw new Exception('Temporary config file does not exist in the virtual file system.');
        }
        $parsedConfig = json_decode(file_get_contents(Config::getConfigFilePath()), true);

        if (getenv('TEST_DB_NAME') === $parsedConfig['database']['name']) {
            fprintf(STDERR, "WARNING: The specified test database is equal to the production database name".
            "To prevent data loss, the test execution has been stopped. Please review the configuration.");
            exit(0);
        }

        $parsedConfig['database']['host'] = getenv('TEST_DB_HOST');
        $parsedConfig['database']['port'] = getenv('TEST_DB_PORT');
        $parsedConfig['database']['name'] = getenv('TEST_DB_NAME');
        file_put_contents(Config::getConfigFilePath(), json_encode($parsedConfig));
    }


    public function setUp(){
        $this->databaseService = $this->container['databaseService'];
    }

    public function testDummy(){
        $this->assertTrue($this->databaseService->checkConnectivity());
    }

    public static function tearDownAfterClass() {
        $client = new Client('mongodb://' . getenv('TEST_DB_HOST') . ':' . getenv('TEST_DB_PORT'));
        $db = $client->selectDatabase(getenv('TEST_DB_NAME'));
        $db->drop();
    }
}
