<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\DataSet\DataSet;
use webignition\BasilModel\DataSet\DataSetCollection;
use webignition\BasilModel\Identifier\IdentifierCollection;
use webignition\BasilModel\Step\Step;
use webignition\BasilModel\Step\StepInterface;
use webignition\BasilModelFactory\Action\ActionFactory;
use webignition\BasilModelFactory\AssertionFactory;
use webignition\BasilModelFactory\IdentifierFactory;
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
     * @dataProvider validateNotValidInvalidDataSetCollectionDataProvider
     * @dataProvider validateNotValidInvalidIdentifierCollectionDataProvider
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
        $assertionWithInvalidComparison = $assertionFactory->createFromAssertionString('".selector" foo');

        return [
            'invalid action: input action missing value' => [
                'step' => new Step([$inputActionMissingValue], []),
                'expectedResult' => new InvalidResult(
                    new Step([$inputActionMissingValue], []),
                    TypeInterface::STEP,
                    StepValidator::REASON_ACTION_INVALID,
                    new InvalidResult(
                        $inputActionMissingValue,
                        TypeInterface::ACTION,
                        ActionValidator::REASON_INPUT_ACTION_VALUE_MISSING
                    )
                ),
            ],
            'invalid assertion: assertion with invalid comparison' => [
                'step' => new Step([], [$assertionWithInvalidComparison]),
                'expectedResult' => new InvalidResult(
                    new Step([], [$assertionWithInvalidComparison]),
                    TypeInterface::STEP,
                    StepValidator::REASON_ASSERTION_INVALID,
                    new InvalidResult(
                        $assertionWithInvalidComparison,
                        TypeInterface::ASSERTION,
                        AssertionValidator::REASON_COMPARISON_INVALID
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

    public function validateNotValidInvalidIdentifierCollectionDataProvider(): array
    {
        $actionFactory = ActionFactory::createFactory();
        $assertionFactory = AssertionFactory::createFactory();
        $identifierFactory = IdentifierFactory::createFactory();

        $actionWithElementParameterValue = $actionFactory->createFromActionString('click $elements.missing');

        $elementIdentifier = $identifierFactory
            ->create('$elements.element_name')
            ->withName('element_name');

        $assertionWithElementParameterExaminedValue = $assertionFactory->createFromAssertionString(
            '$elements.missing exists'
        );

        $assertionWithElementParameterExpectedValue = $assertionFactory->createFromAssertionString(
            '".selector" is $elements.missing'
        );

        $stepWithActionWithElementParameterValue = new Step([$actionWithElementParameterValue], []);
        $stepWithAssertionWithElementParameterExaminedValue = new Step([], [
            $assertionWithElementParameterExaminedValue
        ]);


        $stepWithAssertionWithElementParameterExpectedValue = new Step([], [
            $assertionWithElementParameterExpectedValue
        ]);

        return [
            'action has element parameter value, step has no element identifiers' => [
                'step' => $stepWithActionWithElementParameterValue,
                'expectedResult' => (new InvalidResult(
                    $stepWithActionWithElementParameterValue,
                    TypeInterface::STEP,
                    StepValidator::REASON_ELEMENT_IDENTIFIER_MISSING
                ))->withContext([
                    StepValidator::CONTEXT_ELEMENT_IDENTIFIER_NAME => 'missing',
                    StepValidator::CONTEXT_IDENTIFIER_CONTAINER => $actionWithElementParameterValue,
                ]),
            ],
            'action has element parameter value, step has no matching element' => [
                'step' => $stepWithActionWithElementParameterValue->withIdentifierCollection(new IdentifierCollection([
                    $elementIdentifier,
                ])),
                'expectedResult' => (new InvalidResult(
                    $stepWithActionWithElementParameterValue->withIdentifierCollection(new IdentifierCollection([
                        $elementIdentifier,
                    ])),
                    TypeInterface::STEP,
                    StepValidator::REASON_ELEMENT_IDENTIFIER_MISSING
                ))->withContext([
                    StepValidator::CONTEXT_ELEMENT_IDENTIFIER_NAME => 'missing',
                    StepValidator::CONTEXT_IDENTIFIER_CONTAINER => $actionWithElementParameterValue,
                ]),
            ],
            'assertion has element parameter examined value, step has no elements' => [
                'step' => $stepWithAssertionWithElementParameterExaminedValue,
                'expectedResult' => (new InvalidResult(
                    $stepWithAssertionWithElementParameterExaminedValue,
                    TypeInterface::STEP,
                    StepValidator::REASON_ELEMENT_IDENTIFIER_MISSING
                ))->withContext([
                    StepValidator::CONTEXT_ELEMENT_IDENTIFIER_NAME => 'missing',
                    StepValidator::CONTEXT_IDENTIFIER_CONTAINER => $assertionWithElementParameterExaminedValue,
                ]),
            ],
            'assertion has element parameter expected value, step has no elements' => [
                'step' => $stepWithAssertionWithElementParameterExpectedValue,
                'expectedResult' => (new InvalidResult(
                    $stepWithAssertionWithElementParameterExpectedValue,
                    TypeInterface::STEP,
                    StepValidator::REASON_ELEMENT_IDENTIFIER_MISSING
                ))->withContext([
                    StepValidator::CONTEXT_ELEMENT_IDENTIFIER_NAME => 'missing',
                    StepValidator::CONTEXT_IDENTIFIER_CONTAINER => $assertionWithElementParameterExpectedValue,
                ]),
            ],
            'assertion has element parameter examined value, step has no matching element' => [
                'step' => $stepWithAssertionWithElementParameterExaminedValue->withIdentifierCollection(
                    new IdentifierCollection([
                        $elementIdentifier,
                    ])
                ),
                'expectedResult' => (new InvalidResult(
                    $stepWithAssertionWithElementParameterExaminedValue->withIdentifierCollection(
                        new IdentifierCollection([
                            $elementIdentifier,
                        ])
                    ),
                    TypeInterface::STEP,
                    StepValidator::REASON_ELEMENT_IDENTIFIER_MISSING
                ))->withContext([
                    StepValidator::CONTEXT_ELEMENT_IDENTIFIER_NAME => 'missing',
                    StepValidator::CONTEXT_IDENTIFIER_CONTAINER => $assertionWithElementParameterExaminedValue,
                ]),
            ],
            'assertion has element parameter expected value, step has no matching element' => [
                'step' => $stepWithAssertionWithElementParameterExpectedValue->withIdentifierCollection(
                    new IdentifierCollection([
                        $elementIdentifier,
                    ])
                ),
                'expectedResult' => (new InvalidResult(
                    $stepWithAssertionWithElementParameterExpectedValue->withIdentifierCollection(
                        new IdentifierCollection([
                            $elementIdentifier,
                        ])
                    ),
                    TypeInterface::STEP,
                    StepValidator::REASON_ELEMENT_IDENTIFIER_MISSING
                ))->withContext([
                    StepValidator::CONTEXT_ELEMENT_IDENTIFIER_NAME => 'missing',
                    StepValidator::CONTEXT_IDENTIFIER_CONTAINER => $assertionWithElementParameterExpectedValue,
                ]),
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
        $identifierFactory = IdentifierFactory::createFactory();

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
            'actions with element parameters, assertions' => [
                'step' => (new Step(
                    [
                        $actionFactory->createFromActionString('set $elements.input1 to "input 1 value"'),
                        $actionFactory->createFromActionString('set $elements.input2 to "input 2 value"'),
                    ],
                    [
                        $assertionFactory->createFromAssertionString('".selector" exists'),
                    ]
                ))->withIdentifierCollection(new IdentifierCollection([
                    $identifierFactory->create('".input1"', 'input1'),
                    $identifierFactory->create('".input2"', 'input2'),
                ])),
            ],
            'actions with element parameters, assertions with element parameters' => [
                'step' => (new Step(
                    [
                        $actionFactory->createFromActionString('set $elements.input1 to "input 1 value"'),
                        $actionFactory->createFromActionString('set $elements.input2 to "input 2 value"'),
                    ],
                    [
                        $assertionFactory->createFromAssertionString('$elements.heading exists'),
                    ]
                ))->withIdentifierCollection(new IdentifierCollection([
                    $identifierFactory->create('".input1"', 'input1'),
                    $identifierFactory->create('".input2"', 'input2'),
                    $identifierFactory->create('".heading"', 'heading'),
                ])),
            ],
        ];
    }
}
