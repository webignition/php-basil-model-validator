<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Step\Step;
use webignition\BasilModel\Step\StepInterface;
use webignition\BasilModelFactory\Action\ActionFactory;
use webignition\BasilModelFactory\AssertionFactory;
use webignition\BasilModelValidator\Action\ActionValidator;
use webignition\BasilModelValidator\AssertionValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\StepValidator;

class StepValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StepValidator
     */
    private $stepValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stepValidator = StepValidator::create();
    }

    public function testHandles()
    {
        $this->assertTrue($this->stepValidator->handles(\Mockery::mock(StepInterface::class)));
        $this->assertFalse($this->stepValidator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();
        $expectedResult = InvalidResult::createUnhandledModelResult($model);

        $this->assertEquals($expectedResult, $this->stepValidator->validate($model));
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(StepInterface $step, InvalidResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->stepValidator->validate($step));
    }

    public function validateNotValidDataProvider(): array
    {
        $actionFactory = ActionFactory::createFactory();
        $assertionFactory = AssertionFactory::createFactory();

        $inputActionMissingValue = $actionFactory->createFromActionString('set ".selector" to');
        $stepWithInputActionMissingValue = new Step(
            [
                $inputActionMissingValue,
            ],
            []
        );

        $assertionWithInvalidComparison = $assertionFactory->createFromAssertionString('".selector" foo');

        $stepWithAssertionWithInvalidComparison = new Step(
            [],
            [
                $assertionWithInvalidComparison
            ]
        );

        return [
            'invalid action: input action missing value' => [
                'step' => $stepWithInputActionMissingValue,
                'expectedResult' => new InvalidResult(
                    $stepWithInputActionMissingValue,
                    TypeInterface::STEP,
                    StepValidator::CODE_ACTION_INVALID,
                    new InvalidResult(
                        $inputActionMissingValue,
                        TypeInterface::ACTION,
                        ActionValidator::CODE_INPUT_ACTION_VALUE_MISSING
                    )
                ),
            ],
            'invalid action: assertion with invalid comparison' => [
                'step' => $stepWithAssertionWithInvalidComparison,
                'expectedResult' => new InvalidResult(
                    $stepWithAssertionWithInvalidComparison,
                    TypeInterface::STEP,
                    StepValidator::CODE_ASSERTION_INVALID,
                    new InvalidResult(
                        $assertionWithInvalidComparison,
                        TypeInterface::ASSERTION,
                        AssertionValidator::CODE_COMPARISON_INVALID
                    )
                ),
            ],
        ];
    }

    /**
     * @dataProvider validateIsValidDataProvider
     */
    public function testValidateIsValid(StepInterface $step)
    {
        $expectedResult = new ValidResult($step);

        $this->assertEquals($expectedResult, $this->stepValidator->validate($step));
    }

    public function validateIsValidDataProvider(): array
    {
        $actionFactory = ActionFactory::createFactory();
        $assertionFactory = AssertionFactory::createFactory();

        return [
            'no actions, no assertions' => [
                'step' => new Step([], []),
            ],
            'actions and assertions without data sets, without element identifiers' => [
                'step' => new Step(
                    [
                        $actionFactory->createFromActionString('click ".selector"'),
                        $actionFactory->createFromActionString('wait 30'),
                        $actionFactory->createFromActionString('set ".input" to "value"'),
                        $actionFactory->createFromActionString('wait-for ".delayed-field"'),
                        $actionFactory->createFromActionString('set ".delayed-field" to "delayed value"'),
                    ],
                    [
                        $assertionFactory->createFromAssertionString('$page.url is "http://example.com/"'),
                        $assertionFactory->createFromAssertionString('$page.title is "Example"'),
                        $assertionFactory->createFromAssertionString('".delayed-field" is "delayed-value"'),
                    ]
                ),
            ],
        ];
    }
}
