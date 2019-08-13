<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use Nyholm\Psr7\Uri;
use webignition\BasilModel\Identifier\AttributeIdentifier;
use webignition\BasilModel\Identifier\ElementIdentifier;
use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierCollection;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Page\Page;
use webignition\BasilModel\Page\PageInterface;
use webignition\BasilModel\Value\LiteralValue;
use webignition\BasilModel\Value\ObjectNames;
use webignition\BasilModel\Value\ObjectValue;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelValidator\PageValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class PageValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PageValidator
     */
    private $pageValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pageValidator = PageValidator::create();
    }

    public function testHandles()
    {
        $this->assertTrue($this->pageValidator->handles(\Mockery::mock(PageInterface::class)));
        $this->assertFalse($this->pageValidator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();
        $expectedResult = InvalidResult::createUnhandledModelResult($model);

        $this->assertEquals($expectedResult, $this->pageValidator->validate($model));
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(PageInterface $page, InvalidResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->pageValidator->validate($page));
    }

    public function validateNotValidDataProvider(): array
    {
        $emptyUriPage = new Page(
            new Uri(''),
            new IdentifierCollection()
        );

        $pageElementReferenceIdentifier = (new Identifier(
            IdentifierTypes::PAGE_ELEMENT_REFERENCE,
            new ObjectValue(
                ValueTypes::PAGE_ELEMENT_REFERENCE,
                'page_import_name.elements.element_name',
                ObjectNames::PAGE,
                'element_name'
            )
        ))->withName('name');

        $pageWithPageElementReferenceIdentifier = new Page(
            new Uri('http://example.com/'),
            new IdentifierCollection([
                $pageElementReferenceIdentifier,
            ])
        );

        $elementParameterIdentifier = (new Identifier(
            IdentifierTypes::ELEMENT_PARAMETER,
            new ObjectValue(
                ValueTypes::ELEMENT_PARAMETER,
                '$elements.element_name',
                ObjectNames::ELEMENT,
                'element_name'
            )
        ))->withName('name');

        $pageWithElementParameterIdentifier = new Page(
            new Uri('http://example.com/'),
            new IdentifierCollection([
                $elementParameterIdentifier,
            ])
        );

        $attributeIdentifier = (new AttributeIdentifier(
            new ElementIdentifier(
                LiteralValue::createCssSelectorValue('.selector')
            ),
            'attribute_name'
        ))->withName('name');

        $pageWithAttributeIdentifier = new Page(
            new Uri('http://example.com/'),
            new IdentifierCollection([
                $attributeIdentifier,
            ])
        );

        return [
            'empty uri' => [
                'page' => new Page(
                    new Uri(''),
                    new IdentifierCollection()
                ),
                'expectedResult' => new InvalidResult(
                    $emptyUriPage,
                    TypeInterface::PAGE,
                    PageValidator::REASON_URL_MISSING
                ),
            ],
            'invalid identifier: page element reference' => [
                'page' => $pageWithPageElementReferenceIdentifier,
                'expectedResult' => (new InvalidResult(
                    $pageWithPageElementReferenceIdentifier,
                    TypeInterface::PAGE,
                    PageValidator::REASON_INVALID_IDENTIFIER_TYPE
                ))->withContext([
                    PageValidator::CONTEXT_IDENTIFIER_NAME => $pageElementReferenceIdentifier,
                ]),
            ],
            'invalid identifier: element parameter reference' => [
                'page' => $pageWithElementParameterIdentifier,
                'expectedResult' => (new InvalidResult(
                    $pageWithElementParameterIdentifier,
                    TypeInterface::PAGE,
                    PageValidator::REASON_INVALID_IDENTIFIER_TYPE
                ))->withContext([
                    PageValidator::CONTEXT_IDENTIFIER_NAME => $elementParameterIdentifier,
                ]),
            ],
            'invalid identifier: attribute identifier' => [
                'page' => $pageWithAttributeIdentifier,
                'expectedResult' => (new InvalidResult(
                    $pageWithAttributeIdentifier,
                    TypeInterface::PAGE,
                    PageValidator::REASON_INVALID_IDENTIFIER_TYPE
                ))->withContext([
                    PageValidator::CONTEXT_IDENTIFIER_NAME => $attributeIdentifier,
                ]),
            ],
        ];
    }

    /**
     * @dataProvider validateIsValidDataProvider
     */
    public function testValidateIsValid(PageInterface $page)
    {
        $this->assertEquals(new ValidResult($page), $this->pageValidator->validate($page));
    }

    public function validateIsValidDataProvider(): array
    {
        return [
            'empty identifier collection' => [
                'page' => new Page(new Uri('http://example.com/'), new IdentifierCollection()),
            ],
            'non-empty identifier collection' => [
                'page' => new Page(new Uri('http://example.com/'), new IdentifierCollection([
                    new ElementIdentifier(
                        LiteralValue::createCssSelectorValue('.selector')
                    ),
                    new ElementIdentifier(
                        LiteralValue::createXpathExpressionValue('//h1')
                    ),
                ])),
            ],
        ];
    }
}
