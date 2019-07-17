<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\DataSet\DataSet;
use webignition\BasilModel\DataSet\DataSetCollection;
use webignition\BasilModel\Step\Step;
use webignition\BasilModel\Step\StepInterface;
use webignition\BasilModelFactory\Action\ActionFactory;
use webignition\BasilModelFactory\AssertionFactory;
use webignition\BasilModelValidator\Action\ActionValidator;
use webignition\BasilModelValidator\AssertionValidator;
use webignition\BasilModelValidator\DataSetValidator;
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
                $assertionWithInvalidComparison,
            ]
        );

        $inputActionWithDataParameterValue = $actionFactory->createFromActionString('set ".selector" to $data.key');

        $stepWithInputActionWithDataParameterValue = new Step(
            [
                $inputActionWithDataParameterValue,
            ],
            []
        );

        $dataSet = new DataSet('0', [
            'foo' => 'bar',
        ]);

        $assertionWithDataParameterValue = $assertionFactory->createFromAssertionString('".selector" is $data.key');

        $stepWithAssertionWithDataParameterValue = new Step(
            [],
            [
                $assertionWithDataParameterValue,
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
            'invalid action: has data parameter value, step has no data sets' => [
                'step' => $stepWithInputActionWithDataParameterValue,
                'expectedResult' => (new InvalidResult(
                    $stepWithInputActionWithDataParameterValue,
                    TypeInterface::STEP,
                    StepValidator::CODE_DATA_SET_EMPTY
                ))->withContext([
                    DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'key',
                    StepValidator::CONTEXT_VALUE_CONTAINER => $inputActionWithDataParameterValue,
                ]),
            ],
            'invalid action: has data parameter value, step has no matching data sets' => [
                'step' => $stepWithInputActionWithDataParameterValue->withDataSetCollection(new DataSetCollection([
                    $dataSet,
                ])),
                'expectedResult' => new InvalidResult(
                    $stepWithInputActionWithDataParameterValue->withDataSetCollection(new DataSetCollection([
                        $dataSet,
                    ])),
                    TypeInterface::STEP,
                    StepValidator::CODE_DATA_SET_INCOMPLETE,
                    (new InvalidResult(
                        $dataSet,
                        TypeInterface::DATA_SET,
                        DataSetValidator::CODE_DATA_SET_INCOMPLETE
                    ))->withContext([
                        DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'key',
                        StepValidator::CONTEXT_VALUE_CONTAINER => $inputActionWithDataParameterValue,
                    ])
                ),
            ],
            'invalid assertion: has data parameter value, step has no data sets' => [
                'step' => $stepWithAssertionWithDataParameterValue,
                'expectedResult' => (new InvalidResult(
                    $stepWithAssertionWithDataParameterValue,
                    TypeInterface::STEP,
                    StepValidator::CODE_DATA_SET_EMPTY
                ))->withContext([
                    DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'key',
                    StepValidator::CONTEXT_VALUE_CONTAINER => $assertionWithDataParameterValue,
                ]),
            ],
            'invalid assertion: has data parameter value, step has no matching data sets' => [
                'step' => $stepWithAssertionWithDataParameterValue->withDataSetCollection(new DataSetCollection([
                    $dataSet,
                ])),
                'expectedResult' => new InvalidResult(
                    $stepWithAssertionWithDataParameterValue->withDataSetCollection(new DataSetCollection([
                        $dataSet,
                    ])),
                    TypeInterface::STEP,
                    StepValidator::CODE_DATA_SET_INCOMPLETE,
                    (new InvalidResult(
                        $dataSet,
                        TypeInterface::DATA_SET,
                        DataSetValidator::CODE_DATA_SET_INCOMPLETE
                    ))->withContext([
                        DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'key',
                        StepValidator::CONTEXT_VALUE_CONTAINER => $assertionWithDataParameterValue,
                    ])
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
            'actions with data parameters, assertions' => [
                'step' => (new Step(
                    [
                        $actionFactory->createFromActionString('set ".input1" to $data.input1_parameter_name'),
                        $actionFactory->createFromActionString('set ".input2" to $data.input2_parameter_name'),
                    ],
                    [
                        $assertionFactory->createFromAssertionString('$page.url is "http://example.com/"'),
                    ]
                ))->withDataSetCollection(DataSetCollection::fromArray([
                    [
                        'input1_parameter_name' => 'input 1 value 1',
                        'input2_parameter_name' => 'input 2 value 1',
                    ],
                    [
                        'input1_parameter_name' => 'input 1 value 2',
                        'input2_parameter_name' => 'input 2 value 2',
                    ],
                ])),
            ],
            'actions with data parameters with superfluous values, assertions' => [
                'step' => (new Step(
                    [
                        $actionFactory->createFromActionString('set ".input1" to $data.input1_parameter_name'),
                        $actionFactory->createFromActionString('set ".input2" to $data.input2_parameter_name'),
                    ],
                    [
                        $assertionFactory->createFromAssertionString('$page.url is "http://example.com/"'),
                    ]
                ))->withDataSetCollection(DataSetCollection::fromArray([
                    [
                        'input1_parameter_name' => 'input 1 value 1',
                        'input2_parameter_name' => 'input 2 value 1',
                        'foo' => 'bar',
                    ],
                    [
                        'input1_parameter_name' => 'input 1 value 2',
                        'input2_parameter_name' => 'input 2 value 2',
                        'foo' => 'bar',
                    ],
                ])),
            ],
            'actions with data parameters, assertions with data parameters' => [
                'step' => (new Step(
                    [
                        $actionFactory->createFromActionString('set ".input1" to $data.input1_parameter_name'),
                        $actionFactory->createFromActionString('set ".input2" to $data.input2_parameter_name'),
                    ],
                    [
                        $assertionFactory->createFromAssertionString('$page.url is $data.url'),
                    ]
                ))->withDataSetCollection(DataSetCollection::fromArray([
                    [
                        'input1_parameter_name' => 'input 1 value 1',
                        'input2_parameter_name' => 'input 2 value 1',
                        'url' => 'http://example.com/',
                    ],
                    [
                        'input1_parameter_name' => 'input 1 value 2',
                        'input2_parameter_name' => 'input 2 value 2',
                        'url' => 'http://example.com/',
                    ],
                ])),
            ],
        ];
    }
}
