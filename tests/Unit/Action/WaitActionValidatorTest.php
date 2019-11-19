<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Tests\Unit\Action;

use webignition\BasilModel\Action\WaitAction;
use webignition\BasilModel\Action\WaitActionInterface;
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
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(WaitActionInterface $action, ResultInterface $expectedResult)
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
        $action = ActionFactory::createFactory()->createFromActionString($actionString);

        if ($action instanceof WaitActionInterface) {
            $this->assertEquals(new ValidResult($action), $this->waitActionValidator->validate($action));
        }
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
