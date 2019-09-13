<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

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

    public function testHandles()
    {
        $this->assertTrue($this->actionValidator->handles(\Mockery::mock(ActionInterface::class)));
        $this->assertFalse($this->actionValidator->handles(new \stdClass()));
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

    public function testValidateWrongObjectType()
    {
        $expectedResult = InvalidResult::createUnhandledModelResult(new \stdClass());
        $returnedResult = $this->actionValidator->validate(new \stdClass());

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
