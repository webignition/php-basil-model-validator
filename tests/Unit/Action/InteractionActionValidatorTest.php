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
use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelFactory\Action\ActionFactory;
use webignition\BasilModelFactory\IdentifierFactory;
use webignition\BasilModelFactory\ValueFactory;
use webignition\BasilModelValidator\Action\ActionValidator;
use webignition\BasilModelValidator\Action\InteractionActionValidator;
use webignition\BasilModelValidator\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValueValidator;

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
                'action' => new WaitAction('wait 20', new Value(ValueTypes::STRING, '20')),
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
        $identifierFactory = IdentifierFactory::createFactory();
        $valueFactory = ValueFactory::createFactory();

        $interactionActionWithoutIdentifier = $actionFactory->createFromActionString('click');

        $interactionActionWithUnactionableIdentifier = $actionFactory->createFromActionString('click $page.url');

        $invalidIdentifier = $identifierFactory->create('$page.foo');
        $invalidValue = $valueFactory->createFromValueString('$page.foo');

        $interactionActionWithInvalidIdentifier = $actionFactory->createFromActionString(
            'click $page.foo'
        );

        return [
            'interaction action without identifier' => [
                'action' => $interactionActionWithoutIdentifier,
                'expectedResult' => new InvalidResult(
                    $interactionActionWithoutIdentifier,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_INTERACTION_ACTION_IDENTIFIER_MISSING
                ),
            ],
            'interaction action with unactionable identifier' => [
                'action' => $interactionActionWithUnactionableIdentifier,
                'expectedResult' => new InvalidResult(
                    $interactionActionWithUnactionableIdentifier,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_UNACTIONABLE_IDENTIFIER
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
                        IdentifierValidator::REASON_VALUE_INVALID,
                        new InvalidResult(
                            $invalidValue,
                            TypeInterface::VALUE,
                            ValueValidator::REASON_PROPERTY_NAME_INVALID
                        )
                    )
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
