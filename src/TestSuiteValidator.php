<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator;

use webignition\BasilModel\TestSuite\TestSuiteInterface;
use webignition\BasilModelValidator\Test\TestValidator;
use webignition\BasilValidationResult\InvalidResult;
use webignition\BasilValidationResult\InvalidResultInterface;
use webignition\BasilValidationResult\ResultInterface;
use webignition\BasilValidationResult\ValidResult;

class TestSuiteValidator
{
    public const REASON_NO_TESTS = 'test-suite-no-tests';
    public const REASON_TEST_INVALID = 'test-suite-test-invalid';

    private $testValidator;

    public function __construct(TestValidator $testValidator)
    {
        $this->testValidator = $testValidator;
    }

    public static function create(): TestSuiteValidator
    {
        return new TestSuiteValidator(
            TestValidator::create()
        );
    }

    public function validate(TestSuiteInterface $testSuite): ResultInterface
    {
        $tests = $testSuite->getTests();
        if (0 === count($tests)) {
            return $this->createInvalidResult($testSuite, self::REASON_NO_TESTS);
        }

        foreach ($tests as $test) {
            $testValidationResult = $this->testValidator->validate($test);

            if ($testValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult($testSuite, self::REASON_TEST_INVALID, $testValidationResult);
            }
        }

        return new ValidResult($testSuite);
    }

    private function createInvalidResult(
        TestSuiteInterface $testSuite,
        string $reason,
        ?InvalidResultInterface $invalidResult = null
    ): ResultInterface {
        return new InvalidResult($testSuite, TypeInterface::TEST_SUITE, $reason, $invalidResult);
    }
}
