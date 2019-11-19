<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Action\WaitAction;
use webignition\BasilModel\DataSet\DataSet;
use webignition\BasilModel\DataSet\DataSetCollection;
use webignition\BasilModel\Step\Step;
use webignition\BasilModel\Step\StepInterface;
use webignition\BasilModel\Value\LiteralValue;
use webignition\BasilModel\Value\ObjectValue;
use webignition\BasilModel\Value\ObjectValueType;
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
use webignition\BasilModelValidator\ValueValidator;

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
     * @dataProvider validateNotValidInvalidDataSetCollectionDataProvider
     */
    public function testValidateNotValid(StepInterface $step, InvalidResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->stepValidator->validate($step));
    }

    public function validateNotValidDataProvider(): array
    {
        $assertionFactory = AssertionFactory::createFactory();

        $invalidValue = new ObjectValue(ObjectValueType::PAGE_PROPERTY, '$page.foo', 'foo');

        $assertionWithInvalidValue = $assertionFactory->createFromAssertionString('$page.foo exists');

        $waitActionNoDuration = new WaitAction('wait', new LiteralValue(''));

        return [
            'invalid action: wait action missing duration' => [
                'step' => new Step([$waitActionNoDuration], []),
                'expectedResult' => new InvalidResult(
                    new Step([$waitActionNoDuration], []),
                    TypeInterface::STEP,
                    StepValidator::REASON_ACTION_INVALID,
                    new InvalidResult(
                        $waitActionNoDuration,
                        TypeInterface::ACTION,
                        ActionValidator::REASON_WAIT_ACTION_DURATION_MISSING
                    )
                ),
            ],
            'invalid assertion: assertion with invalid examined value' => [
                'step' => new Step([], [$assertionWithInvalidValue]),
                'expectedResult' => new InvalidResult(
                    new Step([], [$assertionWithInvalidValue]),
                    TypeInterface::STEP,
                    StepValidator::REASON_ASSERTION_INVALID,
                    new InvalidResult(
                        $assertionWithInvalidValue,
                        TypeInterface::ASSERTION,
                        AssertionValidator::REASON_EXAMINED_VALUE_INVALID,
                        new InvalidResult(
                            $invalidValue,
                            TypeInterface::VALUE,
                            ValueValidator::REASON_PROPERTY_NAME_INVALID
                        )
                    )
                ),
            ],
            'no assertions' => [
                'step' => new Step([], []),
                'expectedResult' => new InvalidResult(
                    new Step([], []),
                    TypeInterface::STEP,
                    StepValidator::REASON_NO_ASSERTIONS
                ),
            ],
        ];
    }

    public function validateNotValidInvalidDataSetCollectionDataProvider(): array
    {
        $actionFactory = ActionFactory::createFactory();
        $assertionFactory = AssertionFactory::createFactory();

        $dataSet = new DataSet('0', ['foo' => 'bar']);
        $inputActionWithDataParameterValue = $actionFactory->createFromActionString('set ".selector" to $data.key');

        $assertionWithDataParameterExaminedValue = $assertionFactory->createFromAssertionString(
            '$data.key is "foo"'
        );

        $assertionWithDataParameterExpectedValue = $assertionFactory->createFromAssertionString(
            '".selector" is $data.key'
        );

        $stepWithInputActionWithDataParameterValue = new Step([$inputActionWithDataParameterValue], []);
        $stepWithAssertionWithDataParameterExaminedValue = new Step([], [$assertionWithDataParameterExaminedValue]);
        $stepWithAssertionWithDataParameterExpectedValue = new Step([], [$assertionWithDataParameterExpectedValue]);

        return [
            'action has data parameter value, step has no data sets' => [
                'step' => $stepWithInputActionWithDataParameterValue,
                'expectedResult' => (new InvalidResult(
                    $stepWithInputActionWithDataParameterValue,
                    TypeInterface::STEP,
                    StepValidator::REASON_DATA_SET_EMPTY
                ))->withContext([
                    DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'key',
                    StepValidator::CONTEXT_VALUE_CONTAINER => $inputActionWithDataParameterValue,
                ]),
            ],
            'action has data parameter value, step has no matching data sets' => [
                'step' => $stepWithInputActionWithDataParameterValue->withDataSetCollection(new DataSetCollection([
                    $dataSet,
                ])),
                'expectedResult' => new InvalidResult(
                    $stepWithInputActionWithDataParameterValue->withDataSetCollection(new DataSetCollection([
                        $dataSet,
                    ])),
                    TypeInterface::STEP,
                    StepValidator::REASON_DATA_SET_INCOMPLETE,
                    (new InvalidResult(
                        $dataSet,
                        TypeInterface::DATA_SET,
                        DataSetValidator::REASON_DATA_SET_INCOMPLETE
                    ))->withContext([
                        DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'key',
                        StepValidator::CONTEXT_VALUE_CONTAINER => $inputActionWithDataParameterValue,
                    ])
                ),
            ],
            'assertion has data parameter examined value, step has no data sets' => [
                'step' => $stepWithAssertionWithDataParameterExaminedValue,
                'expectedResult' => (new InvalidResult(
                    $stepWithAssertionWithDataParameterExaminedValue,
                    TypeInterface::STEP,
                    StepValidator::REASON_DATA_SET_EMPTY
                ))->withContext([
                    DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'key',
                    StepValidator::CONTEXT_VALUE_CONTAINER => $assertionWithDataParameterExaminedValue,
                ]),
            ],
            'assertion has data parameter examined value, step has no matching data sets' => [
                'step' => $stepWithAssertionWithDataParameterExaminedValue->withDataSetCollection(
                    new DataSetCollection([$dataSet])
                ),
                'expectedResult' => new InvalidResult(
                    $stepWithAssertionWithDataParameterExaminedValue->withDataSetCollection(new DataSetCollection([
                        $dataSet,
                    ])),
                    TypeInterface::STEP,
                    StepValidator::REASON_DATA_SET_INCOMPLETE,
                    (new InvalidResult(
                        $dataSet,
                        TypeInterface::DATA_SET,
                        DataSetValidator::REASON_DATA_SET_INCOMPLETE
                    ))->withContext([
                        DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'key',
                        StepValidator::CONTEXT_VALUE_CONTAINER => $assertionWithDataParameterExaminedValue,
                    ])
                ),
            ],
            'assertion has data parameter expected value, step has no data sets' => [
                'step' => $stepWithAssertionWithDataParameterExpectedValue,
                'expectedResult' => (new InvalidResult(
                    $stepWithAssertionWithDataParameterExpectedValue,
                    TypeInterface::STEP,
                    StepValidator::REASON_DATA_SET_EMPTY
                ))->withContext([
                    DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'key',
                    StepValidator::CONTEXT_VALUE_CONTAINER => $assertionWithDataParameterExpectedValue,
                ]),
            ],
            'assertion has data parameter expected value, step has no matching data sets' => [
                'step' => $stepWithAssertionWithDataParameterExpectedValue->withDataSetCollection(
                    new DataSetCollection([$dataSet])
                ),
                'expectedResult' => new InvalidResult(
                    $stepWithAssertionWithDataParameterExpectedValue->withDataSetCollection(new DataSetCollection([
                        $dataSet,
                    ])),
                    TypeInterface::STEP,
                    StepValidator::REASON_DATA_SET_INCOMPLETE,
                    (new InvalidResult(
                        $dataSet,
                        TypeInterface::DATA_SET,
                        DataSetValidator::REASON_DATA_SET_INCOMPLETE
                    ))->withContext([
                        DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'key',
                        StepValidator::CONTEXT_VALUE_CONTAINER => $assertionWithDataParameterExpectedValue,
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
            'no actions' => [
                'step' => new Step([], [
                    $assertionFactory->createFromAssertionString('$page.url is "http://example.com/"'),
                ]),
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
