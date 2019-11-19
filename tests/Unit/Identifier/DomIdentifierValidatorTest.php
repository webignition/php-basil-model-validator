<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Tests\Unit\Identifier;

use webignition\BasilModel\Identifier\DomIdentifierInterface;
use webignition\BasilModelValidator\Identifier\DomIdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;

class DomIdentifierValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DomIdentifierValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = DomIdentifierValidator::create();
    }

    public function testHandles()
    {
        $this->assertTrue($this->validator->handles(\Mockery::mock(DomIdentifierInterface::class)));
        $this->assertFalse($this->validator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();

        $expectedResult = InvalidResult::createUnhandledModelResult($model);

        $this->assertEquals($expectedResult, $this->validator->validate($model));
    }
}
