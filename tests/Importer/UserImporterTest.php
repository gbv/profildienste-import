<?php
namespace Importer;

class UserImporterTest extends \BaseTest {

    /**
     * @var UserImporter
     */
    private $userImporter;

    private $validData = [
        'id' => '9706',
        'isil' => 'DE-1',
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

    public function setUp() {
        parent::setUp();
        $this->userImporter = $this->container['userImporter'];
    }

    public function testValidateEmptyData() {
        $data = [];
        $this->assertFalse($this->userImporter->validate($data));
    }

    public function testValidateIdNotString() {

        $data = [
            'id' => ['foo']
        ];
        $this->assertFalse($this->userImporter->validate($data));

        $data['id'] = 1337;

        $this->assertFalse($this->userImporter->validate($data));

        $data['id'] = false;

        $this->assertFalse($this->userImporter->validate($data));
    }

    public function testValidateNoFields() {

        $data = [
            'id' => '9706'
        ];

        $this->assertFalse($this->userImporter->validate($data));
    }

    public function testValidateBudgetsInvalidItems() {

        $data = [
            'id' => '9706',
            'budgets' => ''
        ];
        $this->assertFalse($this->userImporter->validate($data));

        $data['budgets'] = [];
        $this->assertFalse($this->userImporter->validate($data));

        $data['budgets'] = ['foo'];
        $this->assertFalse($this->userImporter->validate($data));

        $data['budgets'] = [[]];
        $this->assertFalse($this->userImporter->validate($data));

        $data['budgets'] = [['name' => '', 'value' => '']];
        $this->assertFalse($this->userImporter->validate($data));

        $data['budgets'] = [['name' => 'foo', 'value' => 200, 'default' => true]];
        $this->assertFalse($this->userImporter->validate($data));

        $data['budgets'] = [['name' => 'foo', 'value' => 'bar']];
        $this->assertFalse($this->userImporter->validate($data));

        $data['budgets'] = [['name' => 'foo', 'value' => 'bar', 'default' => true, 'extraValue' => 'notAllowed']];
        $this->assertFalse($this->userImporter->validate($data));
    }

    public function testValidateSuppliersInvalidItems() {

        $data = [
            'id' => '9706',
            'suppliers' => ''
        ];

        $this->assertFalse($this->userImporter->validate($data));

        $data['suppliers'] = [];
        $this->assertFalse($this->userImporter->validate($data));

        $data['suppliers'] = ['foo'];
        $this->assertFalse($this->userImporter->validate($data));

        $data['suppliers'] = [[]];
        $this->assertFalse($this->userImporter->validate($data));

        $data['suppliers'] = [['name' => '', 'value' => '']];
        $this->assertFalse($this->userImporter->validate($data));

        $data['suppliers'] = [['name' => 'foo', 'value' => 200, 'default' => true]];
        $this->assertFalse($this->userImporter->validate($data));

        $data['suppliers'] = [['name' => 'foo', 'value' => 'bar']];
        $this->assertFalse($this->userImporter->validate($data));

        $data['suppliers'] = [
            ['name' => 'foo', 'value' => 'bar', 'default' => true],
            ['name' => 'foo', 'value' => 'bar', 'default' => true]
        ];
        $this->assertFalse($this->userImporter->validate($data));

        $data['suppliers'] = [
            ['name' => 'foo', 'value' => 'bar', 'default' => true],
            ['name' => 'foo', 'value' => 'bar']
        ];
        $this->assertFalse($this->userImporter->validate($data));
    }

    public function testValidateDefaultsInvalidItems() {
        $data = [
            'id' => '9706',
            'defaults' => ''
        ];
        $this->assertFalse($this->userImporter->validate($data));

        $data['defaults'] = [];
        $this->assertFalse($this->userImporter->validate($data));

        $data['defaults'] = ['foo'];
        $this->assertFalse($this->userImporter->validate($data));

        $data['defaults'] = [
            'ssgnr' => ''
        ];
        $this->assertFalse($this->userImporter->validate($data));
    }

    public function testValidateValidDataset() {
        $data = $this->validData;
        $this->assertTrue($this->userImporter->validate($data));
    }

    public function testMissingFields(){
        $fieldsToRemove = array_keys($this->validData);
        foreach ($fieldsToRemove as $remove) {
            $data = $this->validData;
            unset($data[$remove]);
            $this->assertFalse($this->userImporter->validate($data));
        }
    }

}
