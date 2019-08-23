<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Identifier;

use webignition\BasilModel\Identifier\ElementIdentifierInterface;
use webignition\BasilModelValidator\Identifier\ElementIdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;

class ElementIdentifierValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ElementIdentifierValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = ElementIdentifierValidator::create();
    }

    public function testHandles()
    {
        $this->assertTrue($this->validator->handles(\Mockery::mock(ElementIdentifierInterface::class)));
        $this->assertFalse($this->validator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();

        $expectedResult = InvalidResult::createUnhandledModelResult($model);

        $this->assertEquals($expectedResult, $this->validator->validate($model));
    }
}
