<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\InputAction;
use webignition\BasilModel\Action\InteractionAction;
use webignition\BasilModel\Action\NoArgumentsAction;
use webignition\BasilModel\Action\UnrecognisedAction;
use webignition\BasilModel\Action\WaitAction;
use webignition\BasilModel\Identifier\AttributeIdentifier;
use webignition\BasilModel\Identifier\ElementIdentifier;
use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Value\LiteralValue;
use webignition\BasilModelFactory\Action\ActionFactory;
use webignition\BasilModelValidator\Action\ActionValidator;
use webignition\BasilModelValidator\Action\InteractionActionValidator;
use webignition\BasilModelValidator\Identifier\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class InteractionActionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InteractionActionValidator
     */
    private $interactionActionValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interactionActionValidator = InteractionActionValidator::create();
    }

    /**
     * @dataProvider handlesDataProvider
     */
    public function testHandles(ActionInterface $action, bool $expectedHandles)
    {
        $this->assertSame($expectedHandles, $this->interactionActionValidator->handles($action));
    }

    public function handlesDataProvider(): array
    {
        return [
            'input action' => [
                'action' => new InputAction('set', null, null, ''),
                'expectedHandles' => false,
            ],
            'interaction action: click' => [
                'action' => new InteractionAction('click', ActionTypes::CLICK, null, ''),
                'expectedHandles' => true,
            ],
            'interaction action: submit' => [
                'action' => new InteractionAction('submit', ActionTypes::SUBMIT, null, ''),
                'expectedHandles' => true,
            ],
            'interaction action: wait-for' => [
                'action' => new InteractionAction('wait-for', ActionTypes::WAIT_FOR, null, ''),
                'expectedHandles' => true,
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
                'action' => new WaitAction('wait 20', LiteralValue::createStringValue('20')),
                'expectedHandles' => false,
            ],
        ];
    }

    public function testValidateNotValidWrongObjectType()
    {
        $object = new \stdClass();

        $this->assertEquals(
            InvalidResult::createUnhandledModelResult($object),
            $this->interactionActionValidator->validate($object)
        );
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(ActionInterface $action, ResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->interactionActionValidator->validate($action));
    }

    public function validateNotValidDataProvider(): array
    {
        $actionFactory = ActionFactory::createFactory();

        $interactionActionWithoutIdentifier = $actionFactory->createFromActionString('click');

        $invalidIdentifier = new Identifier('foo', LiteralValue::createStringValue('value'));

        $interactionActionWithInvalidIdentifier = new InteractionAction(
            '',
            ActionTypes::CLICK,
            $invalidIdentifier,
            ''
        );

        $attributeIdentifier = new AttributeIdentifier(
            new ElementIdentifier(
                LiteralValue::createCssSelectorValue('.selector')
            ),
            'attribute_name'
        );

        $interactionActionWithAttributeIdentifier = new InteractionAction(
            '',
            ActionTypes::CLICK,
            $attributeIdentifier,
            ''
        );

        return [
            'interaction action without identifier' => [
                'action' => $interactionActionWithoutIdentifier,
                'expectedResult' => new InvalidResult(
                    $interactionActionWithoutIdentifier,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_IDENTIFIER_MISSING
                ),
            ],
            'interaction action with invalid identifier' => [
                'action' => $interactionActionWithInvalidIdentifier,
                'expectedResult' => new InvalidResult(
                    $interactionActionWithInvalidIdentifier,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_INVALID_IDENTIFIER,
                    new InvalidResult(
                        $invalidIdentifier,
                        TypeInterface::IDENTIFIER,
                        IdentifierValidator::REASON_TYPE_INVALID
                    )
                ),
            ],
            'interaction action with attribute identifier' => [
                'action' => $interactionActionWithAttributeIdentifier,
                'expectedResult' => new InvalidResult(
                    $interactionActionWithAttributeIdentifier,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_UNACTIONABLE_IDENTIFIER
                ),
            ],
        ];
    }

    public function testValidateIsValid()
    {
        $action = ActionFactory::createFactory()->createFromActionString('click ".selector"');

        $expectedResult = new ValidResult($action);

        $this->assertEquals($expectedResult, $this->interactionActionValidator->validate($action));
    }
}
