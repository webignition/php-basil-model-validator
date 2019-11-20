<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Identifier\DomIdentifier;
use webignition\BasilModel\Value\DomIdentifierValue;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModelFactory\ValueFactory;
use webignition\BasilModelValidator\TypeInterface;
use webignition\BasilModelValidator\ValueValidator;
use webignition\BasilValidationResult\InvalidResult;
use webignition\BasilValidationResult\ValidResult;

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
            'invalid page property name' => [
                'value' => $valueFactory->createFromValueString('$page.foo'),
                'expectedReason' => ValueValidator::REASON_PROPERTY_NAME_INVALID,
            ],
            'invalid assertion examined value (invalid page property name)' => [
                'value' => $valueFactory->createFromValueString('$page.foo'),
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
