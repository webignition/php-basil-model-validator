<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\InputAction;
use webignition\BasilModel\Action\InteractionAction;
use webignition\BasilModel\Action\NoArgumentsAction;
use webignition\BasilModel\Action\UnrecognisedAction;
use webignition\BasilModel\Action\WaitAction;
use webignition\BasilModelValidator\Action\InvalidResultCode;
use webignition\BasilModelValidator\Action\WaitActionValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class WaitActionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WaitActionValidator
     */
    private $waitActionValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->waitActionValidator = new WaitActionValidator();
    }

    /**
     * @dataProvider handlesDataProvider
     */
    public function testHandles(ActionInterface $action, bool $expectedHandles)
    {
        $this->assertSame($expectedHandles, $this->waitActionValidator->handles($action));
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
            'no arguments action' => [
                'action' => new NoArgumentsAction('reload', '', ''),
                'expectedHandles' => false,
            ],
            'unrecognised action' => [
                'action' => new UnrecognisedAction('foo', '', ''),
                'expectedHandles' => false,
            ],
            'wait action' => [
                'action' => new WaitAction('wait 1', ''),
                'expectedHandles' => true,
            ],
        ];
    }

    public function testValidateNotValidWrongObjectType()
    {
        $object = new \stdClass();

        $this->assertEquals(
            InvalidResult::createUnhandledModelResult($object),
            $this->waitActionValidator->validate($object)
        );
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(ActionInterface $action, ResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->waitActionValidator->validate($action));
    }

    public function validateNotValidDataProvider(): array
    {
        $waitActionNoDuration = new WaitAction('wait', '');

        return [
            'wait action duration missing' => [
                'action' => $waitActionNoDuration,
                'expectedResult' => new InvalidResult(
                    $waitActionNoDuration,
                    TypeInterface::ACTION,
                    InvalidResultCode::CODE_WAIT_ACTION_DURATION_MISSING
                ),
            ],
        ];
    }

    public function testValidateIsValid()
    {
        $action = new WaitAction('wait 5', '5');

        $expectedResult = new ValidResult($action);

        $this->assertEquals($expectedResult, $this->waitActionValidator->validate($action));
    }
}
