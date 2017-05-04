<?php

namespace Config;


/**
 * Class CheckResult
 *
 * Represents the result of the environment check performed by the ValidatorService.
 *
 * @package Config
 */
class CheckResult {

    const PASSED = 0;
    const FAILED = 2;

    private $status;
    private $errors;

    public function __construct($passed, $errors = []) {
        $this->status = $passed ? self::PASSED : self::FAILED;
        $this->errors = $errors;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getErrors(){
        return $this->errors;
    }
}