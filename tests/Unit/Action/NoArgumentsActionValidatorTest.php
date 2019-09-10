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
use webignition\BasilModel\Value\LiteralValue;
use webignition\BasilModelFactory\Action\ActionFactory;
use webignition\BasilModelValidator\Action\NoArgumentsActionValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
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

        $this->noArgumentsActionValidator = NoArgumentsActionValidator::create();
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
                'action' => new InputAction('set', null, null, ''),
                'expectedHandles' => false,
            ],
            'interaction action' => [
                'action' => new InteractionAction('click', '', null, ''),
                'expectedHandles' => false,
            ],
            'no arguments action: reload' => [
                'action' => new NoArgumentsAction('reload', ActionTypes::RELOAD, ''),
                'expectedHandles' => true,
            ],
            'no arguments action: back' => [
                'action' => new NoArgumentsAction('back', ActionTypes::BACK, ''),
                'expectedHandles' => true,
            ],
            'no arguments action: forward' => [
                'action' => new NoArgumentsAction('forward', ActionTypes::FORWARD, ''),
                'expectedHandles' => true,
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

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(ActionInterface $action, ResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->noArgumentsActionValidator->validate($action));
    }

    public function validateNotValidDataProvider(): array
    {
        $noArgumentsActionWrongType = new NoArgumentsAction('foo', 'Foo', '');

        return [
            'no arguments action wrong type' => [
                'action' => $noArgumentsActionWrongType,
                'expectedResult' => InvalidResult::createUnhandledModelResult($noArgumentsActionWrongType),
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

        $this->assertEquals($expectedResult, $this->noArgumentsActionValidator->validate($action));
    }

    public function validateIsValidDataProvider(): array
    {
        return [
            'reload, no arguments' => [
                'actionString' => 'reload',
            ],
            'back, no arguments' => [
                'actionString' => 'back',
            ],
            'forward, no arguments' => [
                'actionString' => 'forward',
            ],
            'reload, has arguments' => [
                'actionString' => 'reload args',
            ],
            'back, has arguments' => [
                'actionString' => 'back args',
            ],
            'forward, has arguments' => [
                'actionString' => 'forward args',
            ],
        ];
    }
}
