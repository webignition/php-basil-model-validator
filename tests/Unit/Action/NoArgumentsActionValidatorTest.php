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
use webignition\BasilModelValidator\Action\NoArgumentsActionValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class NoArgumentsActionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NoArgumentsActionValidator
     */
    private $noArgumentsActionValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->noArgumentsActionValidator = new NoArgumentsActionValidator();
    }

    /**
     * @dataProvider handlesDataProvider
     */
    public function testHandles(ActionInterface $action, bool $expectedHandles)
    {
        $this->assertSame($expectedHandles, $this->noArgumentsActionValidator->handles($action));
    }

    public function handlesDataProvider(): array
    {
        return [
            'input action' => [
                'action' => new InputAction(null, null, ''),
                'expectedHandles' => false,
            ],
            'interaction action' => [
                'action' => new InteractionAction('', null, ''),
                'expectedHandles' => false,
            ],
            'no arguments action: reload' => [
                'action' => new NoArgumentsAction(ActionTypes::RELOAD, ''),
                'expectedHandles' => true,
            ],
            'no arguments action: back' => [
                'action' => new NoArgumentsAction(ActionTypes::BACK, ''),
                'expectedHandles' => true,
            ],
            'no arguments action: forward' => [
                'action' => new NoArgumentsAction(ActionTypes::FORWARD, ''),
                'expectedHandles' => true,
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
        $this->assertEquals($expectedResult, $this->noArgumentsActionValidator->validate($action));
    }

    public function validateNotValidDataProvider(): array
    {
        $noArgumentsActionWrongType = new NoArgumentsAction('Foo', '');

        return [
            'no arguments action wrong type' => [
                'action' => $noArgumentsActionWrongType,
                'expectedResult' => new InvalidResult(
                    $noArgumentsActionWrongType,
                    TypeInterface::UNHANDLED
                ),
            ],
        ];
    }

    /**
     * @dataProvider validateIsValidDataProvider
     */
    public function testValidateIsValid(string $type, string $arguments)
    {
        $action = new NoArgumentsAction($type, $arguments);

        $expectedResult = new ValidResult($action);

        $this->assertEquals($expectedResult, $this->noArgumentsActionValidator->validate($action));
    }

    public function validateIsValidDataProvider(): array
    {
        return [
            'reload, no arguments' => [
                'type' => ActionTypes::RELOAD,
                'arguments' => '',
            ],
            'back, no arguments' => [
                'type' => ActionTypes::BACK,
                'arguments' => '',
            ],
            'forward, no arguments' => [
                'type' => ActionTypes::FORWARD,
                'arguments' => '',
            ],
            'reload, has arguments' => [
                'type' => ActionTypes::RELOAD,
                'arguments' => 'args',
            ],
            'back, has arguments' => [
                'type' => ActionTypes::BACK,
                'arguments' => 'args',
            ],
            'forward, has arguments' => [
                'type' => ActionTypes::FORWARD,
                'arguments' => 'args',
            ],
        ];
    }
}
