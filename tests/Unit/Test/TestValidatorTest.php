<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Tests\Unit\Test;

use webignition\BasilDataStructure\Step as StepData;
use webignition\BasilModel\Step\Step;
use webignition\BasilModel\Test\Configuration;
use webignition\BasilModel\Test\Test;
use webignition\BasilModel\Test\TestInterface;
use webignition\BasilModelFactory\StepFactory;
use webignition\BasilModelValidator\TypeInterface;
use webignition\BasilModelValidator\StepValidator;
use webignition\BasilModelValidator\Test\ConfigurationValidator;
use webignition\BasilModelValidator\Test\TestValidator;
use webignition\BasilValidationResult\InvalidResult;
use webignition\BasilValidationResult\ResultInterface;
use webignition\BasilValidationResult\ValidResult;

class TestValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TestValidator
     */
    private $testValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testValidator = TestValidator::create();
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(TestInterface $test, ResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->testValidator->validate($test));
    }

    public function validateNotValidDataProvider(): array
    {
        $configurationWithEmptyBrowser = new Configuration('', '');
        $testWithInvalidConfiguration = new Test('test name', $configurationWithEmptyBrowser, []);

        $validConfiguration = new Configuration('chrome', 'http://example.com/');

        $testWithNoSteps = new Test('test name', $validConfiguration, []);

        $invalidStep = new Step([], []);
        $testWithInvalidStep = new Test('test name', $validConfiguration, [$invalidStep]);

        return [
            'invalid configuration' => [
                'test' => $testWithInvalidConfiguration,
                'expectedResult' => new InvalidResult(
                    $testWithInvalidConfiguration,
                    TypeInterface::TEST,
                    TestValidator::REASON_CONFIGURATION_INVALID,
                    new InvalidResult(
                        $configurationWithEmptyBrowser,
                        TypeInterface::TEST_CONFIGURATION,
                        ConfigurationValidator::REASON_BROWSER_MISSING
                    )
                ),
            ],
            'no steps' => [
                'test' => $testWithNoSteps,
                'expectedResult' => new InvalidResult(
                    $testWithNoSteps,
                    TypeInterface::TEST,
                    TestValidator::REASON_NO_STEPS
                ),
            ],
            'invalid step' => [
                'test' => $testWithInvalidStep,
                'expectedResult' => new InvalidResult(
                    $testWithInvalidStep,
                    TypeInterface::TEST,
                    TestValidator::REASON_STEP_INVALID,
                    new InvalidResult(
                        $invalidStep,
                        TypeInterface::STEP,
                        StepValidator::REASON_NO_ASSERTIONS
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

        $expectedResult = new ValidResult($test);

        $this->assertEquals($expectedResult, $this->testValidator->validate($test));
    }
}
