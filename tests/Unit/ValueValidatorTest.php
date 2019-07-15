<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModelFactory\ValueFactory;
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

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(ValueInterface $value, int $expectedResultCode)
    {
        $expectedResult = new InvalidResult($value, TypeInterface::VALUE, $expectedResultCode);

        $this->assertEquals($expectedResult, $this->valueValidator->validate($value));
    }

    public function validateNotValidDataProvider(): array
    {
        $valueFactory = ValueFactory::createFactory();

        return [
            'invalid type' => [
                'value' => new Value('foo', ''),
                'expectedResultCode' => ValueValidator::CODE_TYPE_INVALID,
            ],
            'invalid page object property name' => [
                'value' => $valueFactory->createFromValueString('$page.foo'),
                'expectedResultCode' => ValueValidator::CODE_PROPERTY_NAME_INVALID,
            ],
            'invalid browser object property name' => [
                'value' => $valueFactory->createFromValueString('$browser.foo'),
                'expectedResultCode' => ValueValidator::CODE_PROPERTY_NAME_INVALID,
            ],
        ];
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
        $valueFactory = ValueFactory::createFactory();

        return [
            'type: string' => [
                'value' => $valueFactory->createFromValueString('value'),
            ],
            'type: data parameter' => [
                'value' => $valueFactory->createFromValueString('$data.value'),
            ],
            'type: element parameter' => [
                'value' => $valueFactory->createFromValueString('$elements.element_name'),
            ],
            'type: page object property, url' => [
                'value' => $valueFactory->createFromValueString('$page.url'),
            ],
            'type: page object property, title' => [
                'value' => $valueFactory->createFromValueString('$page.title'),
            ],
            'type: browser object property, size' => [
                'value' => $valueFactory->createFromValueString('$browser.size'),
            ],
            'type: page model reference' => [
                'value' => $valueFactory->createFromValueString('page_import_name.elements.element_name'),
            ],
        ];
    }
}
