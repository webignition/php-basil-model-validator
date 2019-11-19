<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Tests\Unit\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModelFactory\Action\ActionFactory;
use webignition\BasilModelValidator\Action\ActionValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ValidResult;

class ActionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ActionValidator
     */
    private $actionValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actionValidator = ActionValidator::create();
    }

    public function testValidateSuccess()
    {
        $actionFactory = ActionFactory::createFactory();

        $action = $actionFactory->createFromActionString('wait 30');
        $expectedResult = new ValidResult($action);

        $actionValidator = new ActionValidator();
        $returnedResult = $actionValidator->validate($action);

        $this->assertEquals($expectedResult, $returnedResult);
    }

    public function testValidateNoActionTypeValidator()
    {
        $action = \Mockery::mock(ActionInterface::class);
        $action
            ->shouldReceive('getType')
            ->andReturn('foo');
        $expectedResult = InvalidResult::createUnhandledModelResult($action);
        $returnedResult = $this->actionValidator->validate($action);

        $this->assertEquals($expectedResult, $returnedResult);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        \Mockery::close();
    }
}
