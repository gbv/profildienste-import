<?php
namespace Importer;

class UserUpdaterTest extends \BaseTest {

    /**
     * @var UserUpdater
     */
    private $userUpdater;

    public function setUp() {
        parent::setUp();
        $this->userUpdater = $this->container['userUpdater'];
    }

    public function testValidateEmptyData() {
        $data = [];
        $this->assertFalse($this->userUpdater->validate($data));
    }

    public function testValidateIdNotString() {

        $data = [
            'id' => ['foo']
        ];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['id'] = 1337;

        $this->assertFalse($this->userUpdater->validate($data));

        $data['id'] = false;

        $this->assertFalse($this->userUpdater->validate($data));
    }

    public function testValidateNoFieldToUpdate() {

        $data = [
            'id' => '9706'
        ];

        $this->assertFalse($this->userUpdater->validate($data));
    }

    public function testValidateBudgetsInvalidItems() {

        $data = [
            'id' => '9706',
            'budgets' => ''
        ];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['budgets'] = [];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['budgets'] = ['foo'];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['budgets'] = [[]];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['budgets'] = [['name' => '', 'value' => '']];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['budgets'] = [['name' => 'foo', 'value' => 200, 'default' => true]];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['budgets'] = [['name' => 'foo', 'value' => 'bar']];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['budgets'] = [['name' => 'foo', 'value' => 'bar', 'default' => true, 'extraValue' => 'notAllowed']];
        $this->assertFalse($this->userUpdater->validate($data));
    }

    public function testValidateSuppliersInvalidItems() {

        $data = [
            'id' => '9706',
            'suppliers' => ''
        ];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['suppliers'] = [];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['suppliers'] = ['foo'];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['suppliers'] = [[]];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['suppliers'] = [['name' => '', 'value' => '']];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['suppliers'] = [['name' => 'foo', 'value' => 200, 'default' => true]];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['suppliers'] = [['name' => 'foo', 'value' => 'bar']];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['suppliers'] = [
            ['name' => 'foo', 'value' => 'bar', 'default' => true],
            ['name' => 'foo', 'value' => 'bar', 'default' => true]
        ];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['suppliers'] = [
            ['name' => 'foo', 'value' => 'bar', 'default' => true],
            ['name' => 'foo', 'value' => 'bar']
        ];
        $this->assertFalse($this->userUpdater->validate($data));
    }

    public function testValidateDefaultsInvalidItems() {
        $data = [
            'id' => '9706',
            'defaults' => ''
        ];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['defaults'] = [];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['defaults'] = ['foo'];
        $this->assertFalse($this->userUpdater->validate($data));

        $data['defaults'] = [
            'ssgnr' => ''
        ];
        $this->assertFalse($this->userUpdater->validate($data));
    }

    public function testValidateValidDataset() {
        $data = [
            'id' => '9706',
            'budgets' => [
                ['name' => 'XXX', 'value' => 'YYY', 'default' => true],
                ['name' => 'ZZZ', 'value' => 'AAA']
            ],
            'suppliers' => [
                ['name' => 'XXX', 'value' => 'YYY', 'default' => true],
                ['name' => 'ZZZ', 'value' => 'AAA']
            ],
            'defaults' => [
                'selcode' => 'XXX',
                'ssgnr' => 'XXX'
            ]
        ];

        $this->assertTrue($this->userUpdater->validate($data));

        unset($data['defaults']);
        $this->assertTrue($this->userUpdater->validate($data));

        unset($data['suppliers']);
        $this->assertTrue($this->userUpdater->validate($data));
    }

}
