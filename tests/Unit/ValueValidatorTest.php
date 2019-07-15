<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelValidator\Result\InvalidResult;
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
                'value' => new Value(ValueTypes::DATA_PARAMETER, '$data.value'),
            ],
            'type: element parameter' => [
                'value' => new Value(ValueTypes::ELEMENT_PARAMETER, '$elements.element_name'),
            ],
            'type: page object property, url' => [
                'value' => new Value(ValueTypes::PAGE_OBJECT_PROPERTY, '$page.url'),
            ],
            'type: page object property, title' => [
                'value' => new Value(ValueTypes::PAGE_OBJECT_PROPERTY, '$page.title'),
            ],
            'type: browser object property, size' => [
                'value' => new Value(ValueTypes::BROWSER_OBJECT_PROPERTY, '$browser.size'),
            ],
            'type: page model reference' => [
                'value' => new Value(ValueTypes::PAGE_MODEL_REFERENCE, 'page_import_name.elements.element_names'),
            ],
        ];
    }
}
