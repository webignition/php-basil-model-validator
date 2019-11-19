<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Tests\Unit\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\InputAction;
use webignition\BasilModel\Action\InteractionAction;
use webignition\BasilModel\Action\NoArgumentsAction;
use webignition\BasilModel\Action\UnrecognisedAction;
use webignition\BasilModel\Action\WaitAction;
use webignition\BasilModel\Identifier\DomIdentifier;
use webignition\BasilModel\Value\LiteralValue;
use webignition\BasilModelFactory\Action\ActionFactory;
use webignition\BasilModelFactory\ValueFactory;
use webignition\BasilModelValidator\Action\ActionValidator;
use webignition\BasilModelValidator\Action\InputActionValidator;
use webignition\BasilModelValidator\Identifier\IdentifierValidator;
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
                'action' => new InputAction(
                    'set ".selector" to ""',
                    new DomIdentifier('.selector'),
                    new LiteralValue(''),
                    ''
                ),
                'expectedHandles' => true,
            ],
            'interaction action' => [
                'action' => new InteractionAction(
                    'click ".selector"',
                    ActionTypes::CLICK,
                    new DomIdentifier('.selector'),
                    '".selector"'
                ),
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
                'action' => new WaitAction('wait 20', new LiteralValue('20')),
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
        $valueFactory = ValueFactory::createFactory();

        $inputActionMissingToKeyword = $actionFactory->createFromActionString('set ".selector" "foo"');
        $inputActionWithIdentifierContainingToKeywordMissingToKeyword = $actionFactory->createFromActionString(
            'set ".selector to value" "foo"'
        );

        $inputActionWithValueContainingToKeywordMissingToKeyword = $actionFactory->createFromActionString(
            'set ".selector" "foo to value"'
        );

        $invalidValue = $valueFactory->createFromValueString('$page.foo');
        $invalidIdentifier = new DomIdentifier('');

        $inputActionWithInvalidIdentifier = new InputAction(
            '',
            $invalidIdentifier,
            new LiteralValue('value'),
            ''
        );

        $inputActionWithInvalidValue = $actionFactory->createFromActionString(
            'set ".selector" to $page.foo'
        );

        $inputActionWithUnactionableValue = $actionFactory->createFromActionString(
            'set ".selector" to page_import_name.elements.element_name'
        );

        $attributeIdentifier = (new DomIdentifier('.selector'))->withAttributeName('attribute_name');

        $inputActionWithAttributeIdentifier = new InputAction(
            'set ".selector".attribute_name to "value"',
            $attributeIdentifier,
            new LiteralValue('value'),
            '".selector".attribute_name to "value"'
        );

        return [
            'input action with identifier and value, missing "to" keyword' => [
                'action' => $inputActionMissingToKeyword,
                'expectedResult' => new InvalidResult(
                    $inputActionMissingToKeyword,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_INPUT_ACTION_TO_KEYWORD_MISSING
                ),
            ],
            'input action with identifier containing "to" keyword and value, missing "to" keyword' => [
                'action' => $inputActionWithIdentifierContainingToKeywordMissingToKeyword,
                'expectedResult' => new InvalidResult(
                    $inputActionWithIdentifierContainingToKeywordMissingToKeyword,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_INPUT_ACTION_TO_KEYWORD_MISSING
                ),
            ],
            'input action with value containing "to" keyword, missing "to" keyword' => [
                'action' => $inputActionWithValueContainingToKeywordMissingToKeyword,
                'expectedResult' => new InvalidResult(
                    $inputActionWithValueContainingToKeywordMissingToKeyword,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_INPUT_ACTION_TO_KEYWORD_MISSING
                ),
            ],
            'input action with invalid identifier' => [
                'action' => $inputActionWithInvalidIdentifier,
                'expectedResult' => new InvalidResult(
                    $inputActionWithInvalidIdentifier,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_INVALID_IDENTIFIER,
                    new InvalidResult(
                        $invalidIdentifier,
                        TypeInterface::IDENTIFIER,
                        IdentifierValidator::REASON_ELEMENT_LOCATOR_MISSING
                    )
                ),
            ],
            'input action with invalid value' => [
                'action' => $inputActionWithInvalidValue,
                'expectedResult' => new InvalidResult(
                    $inputActionWithInvalidValue,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_INVALID_VALUE,
                    new InvalidResult(
                        $invalidValue,
                        TypeInterface::VALUE,
                        ValueValidator::REASON_PROPERTY_NAME_INVALID
                    )
                ),
            ],
            'input action with unactionable value' => [
                'action' => $inputActionWithUnactionableValue,
                'expectedResult' => new InvalidResult(
                    $inputActionWithUnactionableValue,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_INPUT_ACTION_UNACTIONABLE_VALUE
                ),
            ],
            'input action with attribute identifier' => [
                'action' => $inputActionWithAttributeIdentifier,
                'expectedResult' => new InvalidResult(
                    $inputActionWithAttributeIdentifier,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_UNACTIONABLE_IDENTIFIER
                ),
            ],
        ];
    }

    /**
     * @dataProvider validateIsValidDataProvider
     */
    public function testValidateIsValid(string $actionString)
    {
        $action = ActionFactory::createFactory()->createFromActionString($actionString);

        $expectedResult = new ValidResult($action);

        $this->assertEquals($expectedResult, $this->inputActionValidator->validate($action));
    }

    public function validateIsValidDataProvider(): array
    {
        return [
            'set css element selector to string value' => [
                'actionString' => 'set ".selector" to "foo"',
            ],
            'set css element selector to data parameter value' => [
                'actionString' => 'set ".selector" to $data.key',
            ],
            'set css element selector to environment parameter value' => [
                'actionString' => 'set ".selector" to $env.KEY',
            ],
            'set css element selector to browser object parameter value' => [
                'actionString' => 'set ".selector" to $browser.size',
            ],
            'set css element selector to page object parameter value' => [
                'actionString' => 'set ".selector" to $page.url',
            ],
        ];
    }
}
