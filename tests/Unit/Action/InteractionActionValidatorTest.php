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
use webignition\BasilModel\Identifier\DomIdentifier;
use webignition\BasilModel\Value\ElementExpression;
use webignition\BasilModel\Value\ElementExpressionType;
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
                'action' => new InputAction(
                    'set ".selector" to ""',
                    new DomIdentifier(
                        new ElementExpression('.selector', ElementExpressionType::CSS_SELECTOR)
                    ),
                    new LiteralValue(''),
                    ''
                ),
                'expectedHandles' => false,
            ],
            'interaction action: click' => [
                'action' => new InteractionAction(
                    'click ".selector"',
                    ActionTypes::CLICK,
                    new DomIdentifier(
                        new ElementExpression('.selector', ElementExpressionType::CSS_SELECTOR)
                    ),
                    '".selector"'
                ),
                'expectedHandles' => true,
            ],
            'interaction action: submit' => [
                'action' => new InteractionAction(
                    'submit ".selector"',
                    ActionTypes::SUBMIT,
                    new DomIdentifier(
                        new ElementExpression('.selector', ElementExpressionType::CSS_SELECTOR)
                    ),
                    '".selector"'
                ),
                'expectedHandles' => true,
            ],
            'interaction action: wait-for' => [
                'action' => new InteractionAction(
                    'wait-for ".selector"',
                    ActionTypes::WAIT_FOR,
                    new DomIdentifier(
                        new ElementExpression('.selector', ElementExpressionType::CSS_SELECTOR)
                    ),
                    '".selector"'
                ),
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
        $invalidIdentifier = new DomIdentifier(new ElementExpression('', ElementExpressionType::CSS_SELECTOR));

        $interactionActionWithInvalidIdentifier = new InteractionAction(
            '',
            ActionTypes::CLICK,
            $invalidIdentifier,
            ''
        );

        $attributeIdentifier = (new DomIdentifier(
            new ElementExpression('.selector', ElementExpressionType::CSS_SELECTOR)
        ))->withAttributeName('attribute_name');

        $interactionActionWithAttributeIdentifier = new InteractionAction(
            '',
            ActionTypes::CLICK,
            $attributeIdentifier,
            ''
        );

        return [
            'interaction action with invalid identifier' => [
                'action' => $interactionActionWithInvalidIdentifier,
                'expectedResult' => new InvalidResult(
                    $interactionActionWithInvalidIdentifier,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_INVALID_IDENTIFIER,
                    new InvalidResult(
                        $invalidIdentifier,
                        TypeInterface::IDENTIFIER,
                        IdentifierValidator::REASON_ELEMENT_EXPRESSION_MISSING
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
