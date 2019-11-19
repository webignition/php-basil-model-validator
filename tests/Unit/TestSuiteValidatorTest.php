<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilDataStructure\Step as StepData;
use webignition\BasilModel\Test\Configuration;
use webignition\BasilModel\Test\Test;
use webignition\BasilModel\TestSuite\TestSuite;
use webignition\BasilModel\TestSuite\TestSuiteInterface;
use webignition\BasilModelFactory\StepFactory;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\Test\TestValidator;
use webignition\BasilModelValidator\TestSuiteValidator;

class TestSuiteValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TestSuiteValidator
     */
    private $testSuiteValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testSuiteValidator = TestSuiteValidator::create();
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(TestSuiteInterface $testSuite, ResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->testSuiteValidator->validate($testSuite));
    }

    public function validateNotValidDataProvider(): array
    {
        $testSuiteWithNoTests = new TestSuite('test suite name', []);

        $invalidTest = new Test('test name', new Configuration('chrome', 'http://example.com/'), []);
        $testSuiteWithInvalidTest = new TestSuite('test suite name', [$invalidTest]);

        return [
            'no tests' => [
                'testSuite' => $testSuiteWithNoTests,
                'expectedResult' => new InvalidResult(
                    $testSuiteWithNoTests,
                    TypeInterface::TEST_SUITE,
                    TestSuiteValidator::REASON_NO_TESTS
                ),
            ],
            'invalid test' => [
                'testSuite' => $testSuiteWithInvalidTest,
                'expectedResult' => new InvalidResult(
                    $testSuiteWithInvalidTest,
                    TypeInterface::TEST_SUITE,
                    TestSuiteValidator::REASON_TEST_INVALID,
                    new InvalidResult(
                        $invalidTest,
                        TypeInterface::TEST,
                        TestValidator::REASON_NO_STEPS
                    )
                ),
            ],
        ];
    }

    public function testValidateIsValid()
    {
        $stepFactory = StepFactory::createFactory();
        $step = $stepFactory->createFromStepData(new StepData([
            StepData::KEY_USE => 'import_name',
            StepData::KEY_DATA => 'data_provider_import_name',
            StepData::KEY_ACTIONS => [
                'click ".selector"',
            ],
            StepData::KEY_ASSERTIONS => [
                '".selector" exists',
            ],
        ]));

        $test = new Test(
            'test name',
            new Configuration('chrome', 'http://example.com/'),
            [
                $step,
            ]
        );

        $testSuite = new TestSuite(
            'test suite name',
            [
                $test,
            ]
        );

        $expectedResult = new ValidResult($testSuite);

        $this->assertEquals($expectedResult, $this->testSuiteValidator->validate($testSuite));
    }
}
