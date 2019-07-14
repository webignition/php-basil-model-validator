<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModelValidator\Action\ActionValidator;
use webignition\BasilModelValidator\Action\Factory;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\ValidatorInterface;

class ActionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ActionValidator
     */
    private $actionValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actionValidator = Factory::create();
    }

    public function testHandles()
    {
        $this->assertTrue($this->actionValidator->handles(\Mockery::mock(ActionInterface::class)));
        $this->assertFalse($this->actionValidator->handles(new \stdClass()));
    }

    public function testValidateSuccess()
    {
        $action = \Mockery::mock(ActionInterface::class);

        $result = \Mockery::mock(ResultInterface::class);

        $actionTypeValidator = \Mockery::mock(ValidatorInterface::class);
        $actionTypeValidator
            ->shouldReceive('handles')
            ->once()
            ->with($action)
            ->andReturn(true);

        $actionTypeValidator
            ->shouldReceive('validate')
            ->once()
            ->with($action)
            ->andReturn($result);

        $actionValidator = new ActionValidator();
        $actionValidator->addActionTypeValidator($actionTypeValidator);

        $returnedResult = $actionValidator->validate($action);

        $this->assertSame($result, $returnedResult);
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
