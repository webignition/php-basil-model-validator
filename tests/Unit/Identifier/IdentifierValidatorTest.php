<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Identifier;

use webignition\BasilModel\Identifier\DomIdentifier;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\ReferenceIdentifier;
use webignition\BasilModel\Value\DomIdentifierReference;
use webignition\BasilModel\Value\DomIdentifierReferenceType;
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

        $elementLocator = '.selector';
        $emptyElementLocator = '';

        $emptyLocatorIdentifier = new DomIdentifier($emptyElementLocator);

        $identifierWithInvalidParent =
            (new DomIdentifier($elementLocator))->withParentIdentifier($emptyLocatorIdentifier);

        $identifierWithPageElementReference = $identifierFactory->create('page_import.elements.element_name');
        $identifierWithElementParameter = ReferenceIdentifier::createElementReferenceIdentifier(
            new DomIdentifierReference(
                DomIdentifierReferenceType::ELEMENT,
                '$elements.element_name',
                'element_name'
            )
        );

        $attributeIdentifierWithEmptyAttributeName = (new DomIdentifier(
            $elementLocator
        ))->withAttributeName('');

        return [
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
            'empty element locator' => [
                'identifier' => $emptyLocatorIdentifier,
                'expectedResult' => new InvalidResult(
                    $emptyLocatorIdentifier,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_ELEMENT_LOCATOR_MISSING
                ),
            ],
            'invalid parent identifier' => [
                'identifier' => $identifierWithInvalidParent,
                'expectedResult' => new InvalidResult(
                    $identifierWithInvalidParent,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_INVALID_PARENT_IDENTIFIER,
                    new InvalidResult(
                        $emptyLocatorIdentifier,
                        TypeInterface::IDENTIFIER,
                        IdentifierValidator::REASON_ELEMENT_LOCATOR_MISSING
                    )
                ),
            ],
            'attribute identifier with empty attribute name' => [
                'identifier' => $attributeIdentifierWithEmptyAttributeName,
                'expectedResult' => new InvalidResult(
                    $attributeIdentifierWithEmptyAttributeName,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::REASON_ATTRIBUTE_NAME_EMPTY
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
        $elementLocator = '.selector';

        $elementIdentifier = TestIdentifierFactory::createElementIdentifier($elementLocator);
        $attributeIdentifier = $elementIdentifier->withAttributeName('attribute_name');

        $parentIdentifier = new DomIdentifier('.parent');

        return [
            'dom identifier: non-empty element locator' => [
                'identifier' => $elementIdentifier,
            ],
            'dom identifier: non-empty element locator with parent' => [
                'identifier' => $elementIdentifier->withParentIdentifier($parentIdentifier),
            ],
            'dom identifier: with attriute name' => [
                'identifier' => $attributeIdentifier,
            ],
        ];
    }
}
