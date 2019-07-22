<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\TestSuite\TestSuiteInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\Test\TestValidator;

class TestSuiteValidator implements ValidatorInterface
{
    const REASON_NO_TESTS = 'test-suite-no-tests';
    const REASON_TEST_INVALID = 'test-suite-test-invalid';

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

    public function handles(object $model): bool
    {
        return $model instanceof TestSuiteInterface;
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof TestSuiteInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $tests = $model->getTests();
        if (0 === count($tests)) {
            return $this->createInvalidResult($model, self::REASON_NO_TESTS);
        }

        foreach ($tests as $test) {
            $testValidationResult = $this->testValidator->validate($test);

            if ($testValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult($model, self::REASON_TEST_INVALID, $testValidationResult);
            }
        }

        return new ValidResult($model);
    }

    private function createInvalidResult(
        object $model,
        string $reason,
        ?InvalidResultInterface $invalidResult = null
    ): ResultInterface {
        return new InvalidResult($model, TypeInterface::TEST_SUITE, $reason, $invalidResult);
    }
}
