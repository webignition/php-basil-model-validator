<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Value\ObjectValue;
use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelFactory\IdentifierFactory;
use webignition\BasilModelFactory\ValueFactory;
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
        $valueFactory = ValueFactory::createFactory();

        $identifierWithInvalidType = new Identifier('foo', $valueFactory->createFromValueString('value'));
        $identifierWithEmptyValue = new Identifier(IdentifierTypes::CSS_SELECTOR, new Value(ValueTypes::STRING, ''));

        $identifierWithInvalidParent = $identifierFactory
            ->create('".selector"')
            ->withParentIdentifier($identifierWithInvalidType);

        $invalidPageObjectValue = $valueFactory->createFromValueString('$page.foo');

        $identifierWithInvalidPageObjectProperty = new Identifier(
            IdentifierTypes::PAGE_OBJECT_PARAMETER,
            $invalidPageObjectValue
        );

        $identifierWithPageModelElementReference = $identifierFactory->create(
            'page_import.elements.element_name'
        );

        $cssSelectorIdentifierWithNonStringValue = new Identifier(
            IdentifierTypes::CSS_SELECTOR,
            new ObjectValue(
                ValueTypes::BROWSER_OBJECT_PROPERTY,
                '$browser.size',
                'browser',
                'size'
            )
        );

        $xpathExpressionIdentifierWithNonStringValue = new Identifier(
            IdentifierTypes::XPATH_EXPRESSION,
            new ObjectValue(
                ValueTypes::BROWSER_OBJECT_PROPERTY,
                '$browser.size',
                'browser',
                'size'
            )
        );

        $pageObjectIdentifierWithRegularValue = new Identifier(
            IdentifierTypes::PAGE_OBJECT_PARAMETER,
            new Value(
                ValueTypes::PAGE_OBJECT_PROPERTY,
                '$page.title'
            )
        );

        $pageObjectIdentifierWithInvalidObjectValue = new Identifier(
            IdentifierTypes::PAGE_OBJECT_PARAMETER,
            new ObjectValue(
                ValueTypes::PAGE_OBJECT_PROPERTY,
                '$page.title',
                'foo',
                'title'
            )
        );

        $browserObjectIdentifierWithRegularValue = new Identifier(
            IdentifierTypes::BROWSER_OBJECT_PARAMETER,
            new Value(
                ValueTypes::BROWSER_OBJECT_PROPERTY,
                '$browser.size'
            )
        );

        $browserObjectIdentifierWithInvalidObjectValue = new Identifier(
            IdentifierTypes::BROWSER_OBJECT_PARAMETER,
            new ObjectValue(
                ValueTypes::BROWSER_OBJECT_PROPERTY,
                '$browser.size',
                'foo',
                'size'
            )
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
            'invalid type, page model element reference' => [
                'identifier' => $identifierWithPageModelElementReference,
                'expectedResult' => new InvalidResult(
                    $identifierWithPageModelElementReference,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_TYPE_INVALID
                ),
            ],
            'invalid value, empty css selector' => [
                'identifier' => $identifierWithEmptyValue,
                'expectedResult' => new InvalidResult(
                    $identifierWithEmptyValue,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_VALUE_MISSING
                ),
            ],
            'invalid parent identifier' => [
                'identifier' => $identifierWithInvalidParent,
                'expectedResult' => new InvalidResult(
                    $identifierWithInvalidParent,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_INVALID_PARENT_IDENTIFIER,
                    new InvalidResult(
                        $identifierWithInvalidType,
                        TypeInterface::IDENTIFIER,
                        IdentifierValidator::REASON_TYPE_INVALID
                    )
                ),
            ],
            'invalid page object property' => [
                'identifier' => $identifierWithInvalidPageObjectProperty,
                'expectedResult' => new InvalidResult(
                    $identifierWithInvalidPageObjectProperty,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_VALUE_INVALID,
                    new InvalidResult(
                        $invalidPageObjectValue,
                        TypeInterface::VALUE,
                        ValueValidator::REASON_PROPERTY_NAME_INVALID
                    )
                ),
            ],
            'type mismatch, css selector with non-string value' => [
                'identifier' => $cssSelectorIdentifierWithNonStringValue,
                'expectedResult' => new InvalidResult(
                    $cssSelectorIdentifierWithNonStringValue,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_TYPE_MISMATCH
                ),
            ],
            'type mismatch, xpath expression with non-string value' => [
                'identifier' => $xpathExpressionIdentifierWithNonStringValue,
                'expectedResult' => new InvalidResult(
                    $xpathExpressionIdentifierWithNonStringValue,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_TYPE_MISMATCH
                ),
            ],
            'type mismatch, page object property with regular value' => [
                'identifier' => $pageObjectIdentifierWithRegularValue,
                'expectedResult' => new InvalidResult(
                    $pageObjectIdentifierWithRegularValue,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_TYPE_MISMATCH
                ),
            ],
            'type mismatch, page object property with invalid object value' => [
                'identifier' => $pageObjectIdentifierWithInvalidObjectValue,
                'expectedResult' => new InvalidResult(
                    $pageObjectIdentifierWithInvalidObjectValue,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_TYPE_MISMATCH
                ),
            ],
            'type mismatch, browser object property with regular value' => [
                'identifier' => $browserObjectIdentifierWithRegularValue,
                'expectedResult' => new InvalidResult(
                    $browserObjectIdentifierWithRegularValue,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_TYPE_MISMATCH
                ),
            ],
            'type mismatch, browser object property with invalid object value' => [
                'identifier' => $browserObjectIdentifierWithInvalidObjectValue,
                'expectedResult' => new InvalidResult(
                    $browserObjectIdentifierWithInvalidObjectValue,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_TYPE_MISMATCH
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
        $parentIdentifier = $identifierFactory->create('".parent"');

        return [
            'type: css selector' => [
                'identifier' => $identifierFactory->create('".selector"'),
            ],
            'type: css selector with parent' => [
                'identifier' => $identifierFactory->create('".selector"')
                    ->withParentIdentifier($parentIdentifier),
            ],
            'type: xpath expression' => [
                'identifier' => $identifierFactory->create('"//h1"'),
            ],
            'type: page object parameter, url' => [
                'identifier' => $identifierFactory->create('$page.url'),
            ],
            'type: page object parameter, title' => [
                'identifier' => $identifierFactory->create('$page.title'),
            ],
            'type: browser object parameter, size' => [
                'identifier' => $identifierFactory->create('$browser.size'),
            ],
            'type: element parameter' => [
                'identifier' => $identifierFactory->create('$elements.element_name'),
            ],
        ];
    }
}
