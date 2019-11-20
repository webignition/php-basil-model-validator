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

    /**
     * @dataProvider validateIsValidDataProvider
     */
    public function testValidateIsValid(string $actionString)
    {
        $action = ActionFactory::createFactory()->createFromActionString($actionString);

        $this->assertEquals(new ValidResult($action), $this->actionValidator->validate($action));
    }

    public function validateIsValidDataProvider(): array
    {
        return [
            'reload, no args' => [
                'actionString' => 'reload',
            ],
            'back, no args' => [
                'actionString' => 'back',
            ],
            'forward, no args' => [
                'actionString' => 'forward',
            ],
            'reload, with args' => [
                'actionString' => 'reload arg1 arg2',
            ],
            'back, with args' => [
                'actionString' => 'back arg1 arg2',
            ],
            'forward, with args' => [
                'actionString' => 'forward arg1 arg2',
            ],
        ];
    }

    public function testValidateNoActionTypeValidator()
    {
        $action = \Mockery::mock(ActionInterface::class);
        $action
            ->shouldReceive('getType')
            ->andReturn('foo');
        $expectedResult = InvalidResult::createUnhandledSubjectResult($action);
        $returnedResult = $this->actionValidator->validate($action);

        $this->assertEquals($expectedResult, $returnedResult);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        \Mockery::close();
    }
}
