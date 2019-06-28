<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\InputAction;
use webignition\BasilModel\Action\InteractionAction;
use webignition\BasilModel\Action\NoArgumentsAction;
use webignition\BasilModel\Action\UnrecognisedAction;
use webignition\BasilModel\Action\WaitAction;
use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModelValidator\Action\InteractionActionValidator;
use webignition\BasilModelValidator\Action\InvalidResultCode;
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

        $this->interactionActionValidator = new InteractionActionValidator();
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
                'action' => new InputAction(null, null, ''),
                'expectedHandles' => false,
            ],
            'interaction action: click' => [
                'action' => new InteractionAction(ActionTypes::CLICK, null, ''),
                'expectedHandles' => true,
            ],
            'interaction action: submit' => [
                'action' => new InteractionAction(ActionTypes::SUBMIT, null, ''),
                'expectedHandles' => true,
            ],
            'interaction action: wait-for' => [
                'action' => new InteractionAction(ActionTypes::WAIT_FOR, null, ''),
                'expectedHandles' => true,
            ],
            'no arguments action' => [
                'action' => new NoArgumentsAction('', ''),
                'expectedHandles' => false,
            ],
            'unrecognised action' => [
                'action' => new UnrecognisedAction('', ''),
                'expectedHandles' => false,
            ],
            'wait action' => [
                'action' => new WaitAction(''),
                'expectedHandles' => false,
            ],
        ];
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
        $interactionActionWithoutIdentifier = new InteractionAction(
            ActionTypes::CLICK,
            null,
            ''
        );

        return [
            'interaction action without identifier' => [
                'action' => $interactionActionWithoutIdentifier,
                'expectedResult' => new InvalidResult(
                    $interactionActionWithoutIdentifier,
                    TypeInterface::ACTION,
                    InvalidResultCode::CODE_INTERACTION_ACTION_IDENTIFIER_MISSING
                ),
            ],
        ];
    }

    public function testValidateIsValid()
    {
        $action = new InteractionAction(
            ActionTypes::CLICK,
            new Identifier(
                IdentifierTypes::CSS_SELECTOR,
                '.selector'
            ),
            '".selector"'
        );

        $expectedResult = new ValidResult($action);

        $this->assertEquals($expectedResult, $this->interactionActionValidator->validate($action));
    }
}
