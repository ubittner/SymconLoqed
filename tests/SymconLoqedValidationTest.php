<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/Validator.php';

class SymconLoqedValidationTest extends TestCaseSymconValidation
{
    public function testValidateSymconLoqed(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateLoqedModule(): void
    {
        $this->validateModule(__DIR__ . '/../Loqed');
    }
}