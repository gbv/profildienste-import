<?php

namespace Importer;


class TitleImporterTest extends \BaseTest {

    /**
     * @var TitleImporter
     */
    private $titleImporter;

    public function setUp() {
        parent::setUp();
        $this->titleImporter = $this->container['titleImporter'];
    }

    public function testValidateEmptyData() {
        $data = [];
        $this->assertFalse($this->titleImporter->validate($data));
    }

    public function testValidateId() {
        $data = [
            '003@' => [
                '0' => 'ID'
            ]
        ];

        $this->assertTrue($this->titleImporter->validate($data));
    }

}
