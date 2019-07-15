<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\InputAction;
use webignition\BasilModel\Action\InteractionAction;
use webignition\BasilModel\Action\NoArgumentsAction;
use webignition\BasilModel\Action\UnrecognisedAction;
use webignition\BasilModel\Action\WaitAction;
use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelFactory\Action\ActionFactory;
use webignition\BasilModelFactory\IdentifierFactory;
use webignition\BasilModelFactory\ValueFactory;
use webignition\BasilModelValidator\Action\ActionValidator;
use webignition\BasilModelValidator\Action\InputActionValidator;
use webignition\BasilModelValidator\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValueValidator;

class InputActionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InputActionValidator
     */
    private $inputActionValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inputActionValidator = InputActionValidator::create();
    }

    /**
     * @dataProvider handlesDataProvider
     */
    public function testHandles(ActionInterface $action, bool $expectedHandles)
    {
        $this->assertSame($expectedHandles, $this->inputActionValidator->handles($action));
    }

    public function handlesDataProvider(): array
    {
        return [
            'input action' => [
                'action' => new InputAction('set', null, null, ''),
                'expectedHandles' => true,
            ],
            'interaction action' => [
                'action' => new InteractionAction('click', '', null, ''),
                'expectedHandles' => false,
            ],
            'no arguments action' => [
                'action' => new NoArgumentsAction('reload', '', ''),
                'expectedHandles' => false,
            ],
            'unrecognised action' => [
                'action' => new UnrecognisedAction('foo', '', ''),
                'expectedHandles' => false,
            ],
            'wait action' => [
                'action' => new WaitAction('wait 20', ''),
                'expectedHandles' => false,
            ],
        ];
    }

    public function testValidateNotValidWrongObjectType()
    {
        $object = new \stdClass();

        $this->assertEquals(
            InvalidResult::createUnhandledModelResult($object),
            $this->inputActionValidator->validate($object)
        );
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(ActionInterface $action, ResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->inputActionValidator->validate($action));
    }

    public function validateNotValidDataProvider(): array
    {
        $actionFactory = ActionFactory::createFactory();
        $identifierFactory = IdentifierFactory::createFactory();
        $valueFactory = ValueFactory::createFactory();

        $inputActionMissingIdentifier = new InputAction(
            'set to "foo"',
            null,
            new Value(
                ValueTypes::STRING,
                'foo'
            ),
            ' to "foo"'
        );

        $inputActionMissingValue = $actionFactory->createFromActionString('set ".selector" to');
        $inputActionMissingToKeyword = $actionFactory->createFromActionString('set ".selector" "foo"');
        $inputActionWithIdentifierContainingToKeywordMissingToKeyword = $actionFactory->createFromActionString(
            'set ".selector to value" "foo"'
        );

        $inputActionWithValueContainingToKeywordMissingToKeyword = $actionFactory->createFromActionString(
            'set ".selector" "foo to value"'
        );

        $inputActionWithUnactionableIdentifier = $actionFactory->createFromActionString(
            'set $page.url to "value"'
        );

        $invalidIdentifier = $identifierFactory->create('$elements.element_name');

        $inputActionWithInvalidIdentifier = $actionFactory->createFromActionString(
            'set $elements.element_name to "value"'
        );

        $invalidValue = $valueFactory->createFromValueString('$page.foo');

        $inputActionWithInvalidValue = $actionFactory->createFromActionString(
            'set ".selector" to $page.foo'
        );

        return [
            'input action missing identifier' => [
                'action' => $inputActionMissingIdentifier,
                'expectedResult' => new InvalidResult(
                    $inputActionMissingIdentifier,
                    TypeInterface::ACTION,
                    ActionValidator::CODE_INPUT_ACTION_IDENTIFIER_MISSING
                ),
            ],
            'input action missing value' => [
                'action' => $inputActionMissingValue,
                'expectedResult' => new InvalidResult(
                    $inputActionMissingValue,
                    TypeInterface::ACTION,
                    ActionValidator::CODE_INPUT_ACTION_VALUE_MISSING
                ),
            ],
            'input action with identifier and value, missing "to" keyword' => [
                'action' => $inputActionMissingToKeyword,
                'expectedResult' => new InvalidResult(
                    $inputActionMissingToKeyword,
                    TypeInterface::ACTION,
                    ActionValidator::CODE_INPUT_ACTION_TO_KEYWORD_MISSING
                ),
            ],
            'input action with identifier containing "to" keyword and value, missing "to" keyword' => [
                'action' => $inputActionWithIdentifierContainingToKeywordMissingToKeyword,
                'expectedResult' => new InvalidResult(
                    $inputActionWithIdentifierContainingToKeywordMissingToKeyword,
                    TypeInterface::ACTION,
                    ActionValidator::CODE_INPUT_ACTION_TO_KEYWORD_MISSING
                ),
            ],
            'input action with value containing "to" keyword, missing "to" keyword' => [
                'action' => $inputActionWithValueContainingToKeywordMissingToKeyword,
                'expectedResult' => new InvalidResult(
                    $inputActionWithValueContainingToKeywordMissingToKeyword,
                    TypeInterface::ACTION,
                    ActionValidator::CODE_INPUT_ACTION_TO_KEYWORD_MISSING
                ),
            ],
            'input action with unactionable identifier' => [
                'action' => $inputActionWithUnactionableIdentifier,
                'expectedResult' => new InvalidResult(
                    $inputActionWithUnactionableIdentifier,
                    TypeInterface::ACTION,
                    ActionValidator::CODE_UNACTIONABLE_IDENTIFIER
                ),
            ],
            'input action with invalid identifier' => [
                'action' => $inputActionWithInvalidIdentifier,
                'expectedResult' => new InvalidResult(
                    $inputActionWithInvalidIdentifier,
                    TypeInterface::ACTION,
                    ActionValidator::CODE_INVALID_IDENTIFIER,
                    new InvalidResult(
                        $invalidIdentifier,
                        TypeInterface::IDENTIFIER,
                        IdentifierValidator::CODE_TYPE_INVALID
                    )
                ),
            ],
            'input action with invalid value' => [
                'action' => $inputActionWithInvalidValue,
                'expectedResult' => new InvalidResult(
                    $inputActionWithInvalidValue,
                    TypeInterface::ACTION,
                    ActionValidator::CODE_INVALID_VALUE,
                    new InvalidResult(
                        $invalidValue,
                        TypeInterface::VALUE,
                        ValueValidator::CODE_PROPERTY_NAME_INVALID
                    )
                ),
            ],
        ];
    }

    public function testValidateIsValid()
    {
        $action = ActionFactory::createFactory()->createFromActionString('set ".selector" to "foo"');

        $expectedResult = new ValidResult($action);

        $this->assertEquals($expectedResult, $this->inputActionValidator->validate($action));
    }
}
