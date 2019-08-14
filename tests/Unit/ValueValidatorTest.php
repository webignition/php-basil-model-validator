<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Identifier\AttributeIdentifier;
use webignition\BasilModel\Identifier\ElementIdentifier;
use webignition\BasilModel\Value\AttributeValue;
use webignition\BasilModel\Value\ElementValue;
use webignition\BasilModel\Value\LiteralValue;
use webignition\BasilModel\Value\ObjectValue;
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

        $this->valueValidator = ValueValidator::create();
    }

    public function testHandles()
    {
        $this->assertTrue($this->valueValidator->handles(\Mockery::mock(ValueInterface::class)));
        $this->assertFalse($this->valueValidator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();
        $expectedResult = InvalidResult::createUnhandledModelResult($model);

        $this->assertEquals($expectedResult, $this->valueValidator->validate($model));
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(ValueInterface $value, string $expectedReason)
    {
        $expectedResult = new InvalidResult($value, TypeInterface::VALUE, $expectedReason);

        $this->assertEquals($expectedResult, $this->valueValidator->validate($value));
    }

    public function validateNotValidDataProvider(): array
    {
        $valueFactory = ValueFactory::createFactory();

        return [
            'invalid type' => [
                'value' => new ObjectValue('foo', '', '', ''),
                'expectedReason' => ValueValidator::REASON_TYPE_INVALID,
            ],
            'invalid page object property name' => [
                'value' => $valueFactory->createFromValueString('$page.foo'),
                'expectedReason' => ValueValidator::REASON_PROPERTY_NAME_INVALID,
            ],
            'invalid browser object property name' => [
                'value' => $valueFactory->createFromValueString('$browser.foo'),
                'expectedReason' => ValueValidator::REASON_PROPERTY_NAME_INVALID,
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
            'type: css selector' => [
                'value' => $valueFactory->createFromIdentifierString('".selector"'),
            ],
            'type: xpath expression' => [
                'value' => $valueFactory->createFromIdentifierString('"//foo"'),
            ],
            'type: element value' => [
                'value' => new ElementValue(
                    new ElementIdentifier(
                        LiteralValue::createCssSelectorValue('.selector')
                    )
                ),
            ],
            'type: attribute value' => [
                'value' => new AttributeValue(
                    new AttributeIdentifier(
                        new ElementIdentifier(
                            LiteralValue::createCssSelectorValue('.selector')
                        ),
                        'attribute_name'
                    )
                ),
            ],
        ];
    }
}
