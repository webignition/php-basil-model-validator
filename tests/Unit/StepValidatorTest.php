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
     */
    public function testValidateNotValid(StepInterface $step, InvalidResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->stepValidator->validate($step));
    }

    public function validateNotValidDataProvider(): array
    {
        $actionFactory = ActionFactory::createFactory();
        $assertionFactory = AssertionFactory::createFactory();
        $identifierFactory = IdentifierFactory::createFactory();

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

        $actionWithElementParameterValue = $actionFactory->createFromActionString('click $elements.missing');

        $stepWithActionWithElementParameterValue = new Step(
            [
                $actionWithElementParameterValue,
            ],
            []
        );

        $elementIdentifier = $identifierFactory->create('$elements.element_name');
        $elementIdentifier = $elementIdentifier->withName('element_name');

        $assertionWithElementParameterValue = $assertionFactory->createFromAssertionString('$elements.missing exists');

        $stepWithAssertionWithElementIdentifierParameterValue = new Step(
            [],
            [
                $assertionWithElementParameterValue
            ]
        );

        $stepWithNoActionsNoAssertions = new Step([], []);

        return [
            'invalid action: input action missing value' => [
                'step' => $stepWithInputActionMissingValue,
                'expectedResult' => new InvalidResult(
                    $stepWithInputActionMissingValue,
                    TypeInterface::STEP,
                    StepValidator::REASON_ACTION_INVALID,
                    new InvalidResult(
                        $inputActionMissingValue,
                        TypeInterface::ACTION,
                        ActionValidator::REASON_INPUT_ACTION_VALUE_MISSING
                    )
                ),
            ],
            'invalid action: assertion with invalid comparison' => [
                'step' => $stepWithAssertionWithInvalidComparison,
                'expectedResult' => new InvalidResult(
                    $stepWithAssertionWithInvalidComparison,
                    TypeInterface::STEP,
                    StepValidator::REASON_ASSERTION_INVALID,
                    new InvalidResult(
                        $assertionWithInvalidComparison,
                        TypeInterface::ASSERTION,
                        AssertionValidator::REASON_COMPARISON_INVALID
                    )
                ),
            ],
            'invalid data set collection: action has data parameter value, step has no data sets' => [
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
            'invalid data set collection: action has data parameter value, step has no matching data sets' => [
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
            'invalid data set collection: assertion has data parameter value, step has no data sets' => [
                'step' => $stepWithAssertionWithDataParameterValue,
                'expectedResult' => (new InvalidResult(
                    $stepWithAssertionWithDataParameterValue,
                    TypeInterface::STEP,
                    StepValidator::REASON_DATA_SET_EMPTY
                ))->withContext([
                    DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'key',
                    StepValidator::CONTEXT_VALUE_CONTAINER => $assertionWithDataParameterValue,
                ]),
            ],
            'invalid data set collection: assertion has data parameter value, step has no matching data sets' => [
                'step' => $stepWithAssertionWithDataParameterValue->withDataSetCollection(new DataSetCollection([
                    $dataSet,
                ])),
                'expectedResult' => new InvalidResult(
                    $stepWithAssertionWithDataParameterValue->withDataSetCollection(new DataSetCollection([
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
                        StepValidator::CONTEXT_VALUE_CONTAINER => $assertionWithDataParameterValue,
                    ])
                ),
            ],
            'invalid identifier collection: action has element parameter value, step has no element identifiers' => [
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
            'invalid identifier collection: action has element parameter value, step has no matching element' => [
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
            'invalid identifier collection: assertion has element parameter value, step has no element identifiers' => [
                'step' => $stepWithAssertionWithElementIdentifierParameterValue,
                'expectedResult' => (new InvalidResult(
                    $stepWithAssertionWithElementIdentifierParameterValue,
                    TypeInterface::STEP,
                    StepValidator::REASON_ELEMENT_IDENTIFIER_MISSING
                ))->withContext([
                    StepValidator::CONTEXT_ELEMENT_IDENTIFIER_NAME => 'missing',
                    StepValidator::CONTEXT_IDENTIFIER_CONTAINER => $assertionWithElementParameterValue,
                ]),
            ],
            'invalid identifier collection: assertion has element parameter value, step has no matching element' => [
                'step' => $stepWithAssertionWithElementIdentifierParameterValue->withIdentifierCollection(
                    new IdentifierCollection([
                        $elementIdentifier,
                    ])
                ),
                'expectedResult' => (new InvalidResult(
                    $stepWithAssertionWithElementIdentifierParameterValue->withIdentifierCollection(
                        new IdentifierCollection([
                            $elementIdentifier,
                        ])
                    ),
                    TypeInterface::STEP,
                    StepValidator::REASON_ELEMENT_IDENTIFIER_MISSING
                ))->withContext([
                    StepValidator::CONTEXT_ELEMENT_IDENTIFIER_NAME => 'missing',
                    StepValidator::CONTEXT_IDENTIFIER_CONTAINER => $assertionWithElementParameterValue,
                ]),
            ],
            'no assertions' => [
                'step' => $stepWithNoActionsNoAssertions,
                'expectedResult' => new InvalidResult(
                    $stepWithNoActionsNoAssertions,
                    TypeInterface::STEP,
                    StepValidator::REASON_NO_ASSERTIONS
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
