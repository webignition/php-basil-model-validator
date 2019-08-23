<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Identifier;

use webignition\BasilModel\Identifier\AttributeIdentifierInterface;
use webignition\BasilModelValidator\Identifier\AttributeIdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;

class AttributeIdentifierValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AttributeIdentifierValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = AttributeIdentifierValidator::create();
    }

    public function testHandles()
    {
        $this->assertTrue($this->validator->handles(\Mockery::mock(AttributeIdentifierInterface::class)));
        $this->assertFalse($this->validator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();

        $expectedResult = InvalidResult::createUnhandledModelResult($model);

        $this->assertEquals($expectedResult, $this->validator->validate($model));
    }
}
