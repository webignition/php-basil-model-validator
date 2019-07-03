<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Identifier;

use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelValidator\Identifier\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValueValidator;

class ValueValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ValueValidator
     */
    private $valueValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->valueValidator = new ValueValidator();
    }

    public function testHandles()
    {
        $this->assertTrue($this->valueValidator->handles(\Mockery::mock(ValueInterface::class)));
        $this->assertFalse($this->valueValidator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();
        $expectedResult = new InvalidResult($model, TypeInterface::UNHANDLED);

        $this->assertEquals($expectedResult, $this->valueValidator->validate($model));
    }

    public function testValidateNotValid()
    {
        $value = new Value('foo', '');
        $expectedResult = new InvalidResult($value, TypeInterface::VALUE, ValueValidator::CODE_TYPE_INVALID);

        $this->assertEquals($expectedResult, $this->valueValidator->validate($value));
    }

    /**
     * @dataProvider validateIsValidDataProvider
     */
    public function testValidateIsValid(ValueInterface $value)
    {
        $expectedResult = new ValidResult($value);

        $this->assertEquals($expectedResult, $this->valueValidator->validate($value));
    }

    public function validateIsValidDataProvider(): array
    {
        return [
            'type: string' => [
                'value' => new Value(ValueTypes::STRING, 'value'),
            ],
            'type: data parameter' => [
                'value' => new Value(ValueTypes::STRING, '$data.value'),
            ],
            'type: element parameter' => [
                'value' => new Value(ValueTypes::STRING, '$elements.element_name'),
            ],
        ];
    }
}
