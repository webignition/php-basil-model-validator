<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Identifier\DomIdentifier;
use webignition\BasilModel\Value\Assertion\AssertableExaminedValue;
use webignition\BasilModel\Value\DomIdentifierValue;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModel\Value\WrappedValueInterface;
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
        $expectedResultValue = $value instanceof WrappedValueInterface
            ? $value->getWrappedValue()
            : $value;

        $expectedResult = new InvalidResult($expectedResultValue, TypeInterface::VALUE, $expectedReason);

        $this->assertEquals($expectedResult, $this->valueValidator->validate($value));
    }

    public function validateNotValidDataProvider(): array
    {
        $valueFactory = ValueFactory::createFactory();

        return [
            'invalid page property name' => [
                'value' => $valueFactory->createFromValueString('$page.foo'),
                'expectedReason' => ValueValidator::REASON_PROPERTY_NAME_INVALID,
            ],
            'invalid assertion examined value (invalid page property name)' => [
                'value' => new AssertableExaminedValue(
                    $valueFactory->createFromValueString('$page.foo')
                ),
                'expectedReason' => ValueValidator::REASON_PROPERTY_NAME_INVALID,
            ],
            'invalid browser property name' => [
                'value' => $valueFactory->createFromValueString('$browser.foo'),
                'expectedReason' => ValueValidator::REASON_PROPERTY_NAME_INVALID,
            ],
            'element parameter is unactionable' => [
                'value' => $valueFactory->createFromValueString('$elements.element_name'),
                'expectedReason' => ValueValidator::REASON_UNACTIONABLE,
            ],
            'page model reference is unactionable' => [
                'value' => $valueFactory->createFromValueString('page_import_name.elements.element_name'),
                'expectedReason' => ValueValidator::REASON_UNACTIONABLE,
            ],
            'attribute reference is unactionable' => [
                'value' => $valueFactory->createFromValueString('$elements.element_name.attribute_name'),
                'expectedReason' => ValueValidator::REASON_UNACTIONABLE,
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
            'type: page object property, url' => [
                'value' => $valueFactory->createFromValueString('$page.url'),
            ],
            'type: page object property, title' => [
                'value' => $valueFactory->createFromValueString('$page.title'),
            ],
            'type: browser object property, size' => [
                'value' => $valueFactory->createFromValueString('$browser.size'),
            ],
            'type: element value' => [
                'value' => new DomIdentifierValue(
                    new DomIdentifier('.selector')
                ),
            ],
            'type: attribute value' => [
                'value' => new DomIdentifierValue(
                    (new DomIdentifier('.selector'))->withAttributeName('attribute_name')
                ),
            ],
        ];
    }
}
