<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Identifier\ElementIdentifier;
use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Value\LiteralValue;
use webignition\BasilModel\Value\ObjectValue;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelFactory\Identifier\IdentifierFactory;
use webignition\BasilModelValidator\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValueValidator;

class IdentifierValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IdentifierValidator
     */
    private $identifierValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identifierValidator = IdentifierValidator::create();
    }

    public function testHandles()
    {
        $this->assertTrue($this->identifierValidator->handles(\Mockery::mock(IdentifierInterface::class)));
        $this->assertFalse($this->identifierValidator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();

        $expectedResult = InvalidResult::createUnhandledModelResult($model);

        $this->assertEquals($expectedResult, $this->identifierValidator->validate($model));
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(IdentifierInterface $identifier, ResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->identifierValidator->validate($identifier));
    }

    public function validateNotValidDataProvider(): array
    {
        $identifierFactory = IdentifierFactory::createFactory();

        $identifierWithInvalidType = new Identifier('foo', LiteralValue::createStringValue('value'));

        $invalidParentIdentifier = new ElementIdentifier(
            LiteralValue::createStringValue('invalid')
        );

        $identifierWithInvalidParent = (new ElementIdentifier(
            LiteralValue::createCssSelectorValue('.selector')
        ))->withParentIdentifier($invalidParentIdentifier);

        $valueWithInvalidType = new ObjectValue('foo', 'bar', '', '');
        $identifierWithValueWithInvalidType = new Identifier(
            IdentifierTypes::ELEMENT_SELECTOR,
            $valueWithInvalidType
        );

        $identifierWithPageElementReference = $identifierFactory->create('page_import.elements.element_name');
        $identifierWithEmptyElementParameter = new Identifier(
            IdentifierTypes::ELEMENT_PARAMETER,
            new ObjectValue(ValueTypes::ELEMENT_PARAMETER, '', '', '')
        );

        $elementIdentifierWithWrongValueType = new ElementIdentifier(
            LiteralValue::createStringValue('foo')
        );

        $elementParameterIdentifierWithWrongValueType = new Identifier(
            IdentifierTypes::ELEMENT_PARAMETER,
            LiteralValue::createStringValue('foo')
        );

        return [
            'invalid type, unknown type' => [
                'identifier' => $identifierWithInvalidType,
                'expectedResult' => new InvalidResult(
                    $identifierWithInvalidType,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_TYPE_INVALID
                ),
            ],
            'invalid type, page element reference' => [
                'identifier' => $identifierWithPageElementReference,
                'expectedResult' => new InvalidResult(
                    $identifierWithPageElementReference,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_TYPE_INVALID
                ),
            ],
            'empty css selector' => [
                'identifier' => new ElementIdentifier(LiteralValue::createCssSelectorValue('')),
                'expectedResult' => new InvalidResult(
                    new ElementIdentifier(LiteralValue::createCssSelectorValue('')),
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_VALUE_MISSING
                ),
            ],
            'empty xpath expression' => [
                'identifier' => new ElementIdentifier(LiteralValue::createXpathExpressionValue('')),
                'expectedResult' => new InvalidResult(
                    new ElementIdentifier(LiteralValue::createXpathExpressionValue('')),
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_VALUE_MISSING
                ),
            ],
            'empty element parameter' => [
                'identifier' => $identifierWithEmptyElementParameter,
                'expectedResult' => new InvalidResult(
                    $identifierWithEmptyElementParameter,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_VALUE_MISSING
                ),
            ],
            'invalid value' => [
                'identifier' => $identifierWithValueWithInvalidType,
                'expectedResult' => new InvalidResult(
                    $identifierWithValueWithInvalidType,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_VALUE_INVALID,
                    new InvalidResult(
                        $valueWithInvalidType,
                        TypeInterface::VALUE,
                        ValueValidator::REASON_TYPE_INVALID
                    )
                ),
            ],
            'element identifier with wrong value type' => [
                'identifier' => $elementIdentifierWithWrongValueType,
                'expectedResult' => new InvalidResult(
                    $elementIdentifierWithWrongValueType,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_VALUE_TYPE_MISMATCH
                ),
            ],
            'element parameter with wrong value type' => [
                'identifier' => $elementParameterIdentifierWithWrongValueType,
                'expectedResult' => new InvalidResult(
                    $elementParameterIdentifierWithWrongValueType,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_VALUE_TYPE_MISMATCH
                ),
            ],
            'invalid parent identifier' => [
                'identifier' => $identifierWithInvalidParent,
                'expectedResult' => new InvalidResult(
                    $identifierWithInvalidParent,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_INVALID_PARENT_IDENTIFIER,
                    new InvalidResult(
                        $invalidParentIdentifier,
                        TypeInterface::IDENTIFIER,
                        IdentifierValidator::REASON_VALUE_TYPE_MISMATCH
                    )
                ),
            ],
        ];
    }

    /**
     * @dataProvider validateIsValidDataProvider
     */
    public function testValidateIsValid(IdentifierInterface $identifier)
    {
        $expectedResult = new ValidResult($identifier);

        $this->assertEquals($expectedResult, $this->identifierValidator->validate($identifier));
    }

    public function validateIsValidDataProvider(): array
    {
        $identifierFactory = IdentifierFactory::createFactory();

        $parentIdentifier = new ElementIdentifier(
            LiteralValue::createCssSelectorValue('.parent')
        );

        return [
            ' css selector' => [
                'identifier' => $identifierFactory->create('".selector"'),
            ],
            'css selector with parent' => [
                'identifier' => (new ElementIdentifier(
                    LiteralValue::createCssSelectorValue('.selector')
                ))->withParentIdentifier($parentIdentifier),
            ],
            'xpath expression' => [
                'identifier' => $identifierFactory->create('"//h1"'),
            ],
        ];
    }
}
