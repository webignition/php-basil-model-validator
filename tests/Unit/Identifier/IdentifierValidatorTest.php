<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Identifier;

use webignition\BasilModel\Identifier\AttributeIdentifier;
use webignition\BasilModel\Identifier\ElementIdentifier;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\ReferenceIdentifier;
use webignition\BasilModel\Value\CssSelector;
use webignition\BasilModel\Value\ElementReference;
use webignition\BasilModel\Value\XpathExpression;
use webignition\BasilModelFactory\Identifier\IdentifierFactory;
use webignition\BasilModelValidator\Identifier\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilTestIdentifierFactory\TestIdentifierFactory;

class IdentifierValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IdentifierValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = IdentifierValidator::create();
    }

    public function testHandles()
    {
        $this->assertTrue($this->validator->handles(\Mockery::mock(IdentifierInterface::class)));
        $this->assertFalse($this->validator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();

        $expectedResult = InvalidResult::createUnhandledModelResult($model);

        $this->assertEquals($expectedResult, $this->validator->validate($model));
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(IdentifierInterface $identifier, ResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->validator->validate($identifier));
    }

    public function validateNotValidDataProvider(): array
    {
        $identifierFactory = IdentifierFactory::createFactory();

        $emptyCssSelectorIdentifier = new ElementIdentifier(
            new CssSelector('')
        );

        $emptyXpathExpressionIdentifier = new ElementIdentifier(
            new XpathExpression('')
        );

        $identifierWithInvalidParent = (new ElementIdentifier(
            new CssSelector('.selector')
        ))->withParentIdentifier($emptyCssSelectorIdentifier);

        $identifierWithPageElementReference = $identifierFactory->create('page_import.elements.element_name');
        $identifierWithElementParameter = ReferenceIdentifier::createElementReferenceIdentifier(
            new ElementReference(
                '$elements.element_name',
                'element_name'
            )
        );

        $attributeIdentifierWithInvalidElementIdentifier = new AttributeIdentifier(
            $emptyCssSelectorIdentifier,
            'attribute_name'
        );

        $attributeIdentifierWithEmptyAttributeName = new AttributeIdentifier(
            TestIdentifierFactory::createElementIdentifier(new CssSelector('.selector')),
            ''
        );

        return [
//            'invalid type, unknown type' => [
//                'identifier' => $identifierWithInvalidType,
//                'expectedResult' => new InvalidResult(
//                    $identifierWithInvalidType,
//                    TypeInterface::IDENTIFIER,
//                    IdentifierValidator::REASON_TYPE_INVALID
//                ),
//            ],
            'invalid type, page element reference' => [
                'identifier' => $identifierWithPageElementReference,
                'expectedResult' => new InvalidResult(
                    $identifierWithPageElementReference,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_TYPE_INVALID
                ),
            ],
            'invalid type, element parameter' => [
                'identifier' => $identifierWithElementParameter,
                'expectedResult' => new InvalidResult(
                    $identifierWithElementParameter,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_TYPE_INVALID
                ),
            ],
            'empty css selector' => [
                'identifier' => $emptyCssSelectorIdentifier,
                'expectedResult' => new InvalidResult(
                    $emptyCssSelectorIdentifier,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_ELEMENT_EXPRESSION_MISSING
                ),
            ],
            'empty xpath expression' => [
                'identifier' => $emptyXpathExpressionIdentifier,
                'expectedResult' => new InvalidResult(
                    $emptyXpathExpressionIdentifier,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_ELEMENT_EXPRESSION_MISSING
                ),
            ],
            'invalid parent identifier' => [
                'identifier' => $identifierWithInvalidParent,
                'expectedResult' => new InvalidResult(
                    $identifierWithInvalidParent,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_INVALID_PARENT_IDENTIFIER,
                    new InvalidResult(
                        $emptyCssSelectorIdentifier,
                        TypeInterface::IDENTIFIER,
                        IdentifierValidator::REASON_ELEMENT_EXPRESSION_MISSING
                    )
                ),
            ],
            'attribute identifier with invalid element identifier' => [
                'identifier' => $attributeIdentifierWithInvalidElementIdentifier,
                'expectedResult' => new InvalidResult(
                    $attributeIdentifierWithInvalidElementIdentifier,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_INVALID_ELEMENT_IDENTIFIER,
                    new InvalidResult(
                        $emptyCssSelectorIdentifier,
                        TypeInterface::IDENTIFIER,
                        IdentifierValidator::REASON_ELEMENT_EXPRESSION_MISSING
                    )
                ),
            ],
            'attribute identifier with empty attribute name' => [
                'identifier' => $attributeIdentifierWithEmptyAttributeName,
                'expectedResult' => new InvalidResult(
                    $attributeIdentifierWithEmptyAttributeName,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_ATTRIBUTE_NAME_MISSING
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

        $this->assertEquals($expectedResult, $this->validator->validate($identifier));
    }

    public function validateIsValidDataProvider(): array
    {
        $cssSelector = new CssSelector('.selector');

        $parentIdentifier = new ElementIdentifier(
            new CssSelector('.parent')
        );

        return [
            'element identifier: css selector' => [
                'identifier' => TestIdentifierFactory::createElementIdentifier($cssSelector)
            ],
            'element identifier: css selector with parent' => [
                'identifier' => TestIdentifierFactory::createElementIdentifier($cssSelector, 1, $parentIdentifier)
            ],
            'element identifier: xpath expression' => [
                'identifier' => TestIdentifierFactory::createElementIdentifier(new XpathExpression('//h1')),
            ],
            'attribute identifier' => [
                'identifier' => new AttributeIdentifier(
                    TestIdentifierFactory::createElementIdentifier($cssSelector),
                    'attribute_name'
                ),
            ],
        ];
    }
}
