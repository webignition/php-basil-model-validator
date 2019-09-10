<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\InputAction;
use webignition\BasilModel\Action\InteractionAction;
use webignition\BasilModel\Action\NoArgumentsAction;
use webignition\BasilModel\Action\UnrecognisedAction;
use webignition\BasilModel\Action\WaitAction;
use webignition\BasilModel\Value\LiteralValue;
use webignition\BasilModel\Value\PageElementReference;
use webignition\BasilModelFactory\Action\ActionFactory;
use webignition\BasilModelValidator\Action\ActionValidator;
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

        $this->waitActionValidator = WaitActionValidator::create();
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
                'action' => new WaitAction('wait 1', new LiteralValue('1')),
                'expectedHandles' => true,
            ],
        ];
    }

    public function testValidateNotValidWrongObjectType()
    {
        $model = new \stdClass();

        $this->assertEquals(
            InvalidResult::createUnhandledModelResult($model),
            $this->waitActionValidator->validate($model)
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
        $waitActionNoDuration = new WaitAction('wait', new LiteralValue(''));
        $waitActionWithUnactionableDuration = new WaitAction(
            'wait page_import_name.elements.element_name',
            new PageElementReference(
                'page_import_name.elements.element_name',
                'page_import_name',
                'element_name'
            )
        );

        return [
            'wait action duration missing' => [
                'action' => $waitActionNoDuration,
                'expectedResult' => new InvalidResult(
                    $waitActionNoDuration,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_WAIT_ACTION_DURATION_MISSING
                ),
            ],
            'wait action duration unactionable' => [
                'action' => $waitActionWithUnactionableDuration,
                'expectedResult' => new InvalidResult(
                    $waitActionWithUnactionableDuration,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_WAIT_ACTION_DURATION_UNACTIONABLE
                ),
            ],
        ];
    }

    /**
     * @dataProvider validateIsValidDataProvider
     */
    public function testValidateIsValid(string $actionString)
    {
        $actionFactory = ActionFactory::createFactory();
        $action = $actionFactory->createFromActionString($actionString);

        $expectedResult = new ValidResult($action);

        $this->assertEquals($expectedResult, $this->waitActionValidator->validate($action));
    }

    public function validateIsValidDataProvider(): array
    {
        return [
            'string value' => [
                'actionString' => 'wait 5',
            ],
            'data parameter value' => [
                'actionString' => 'wait $data.duration',
            ],
            'environment parameter value' => [
                'actionString' => 'wait $env.DURATION',
            ],
        ];
    }
}
