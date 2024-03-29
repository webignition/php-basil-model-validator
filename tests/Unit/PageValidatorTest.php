<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Tests\Unit;

use Nyholm\Psr7\Uri;
use webignition\BasilModel\Identifier\DomIdentifier;
use webignition\BasilModel\Identifier\IdentifierCollection;
use webignition\BasilModel\Identifier\ReferenceIdentifier;
use webignition\BasilModel\Page\Page;
use webignition\BasilModel\Page\PageInterface;
use webignition\BasilModel\Value\DomIdentifierReference;
use webignition\BasilModel\Value\DomIdentifierReferenceType;
use webignition\BasilModel\Value\PageElementReference;
use webignition\BasilModelValidator\PageValidator;
use webignition\BasilModelValidator\TypeInterface;
use webignition\BasilTestIdentifierFactory\TestIdentifierFactory;
use webignition\BasilValidationResult\InvalidResult;
use webignition\BasilValidationResult\InvalidResultInterface;
use webignition\BasilValidationResult\ValidResult;

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

        $pageElementReferenceIdentifier = (ReferenceIdentifier::createPageElementReferenceIdentifier(
            new PageElementReference(
                'page_import_name.elements.element_name',
                'page_import_name',
                'element_name'
            )
        ))->withName('name');

        $pageWithPageElementReferenceIdentifier = new Page(
            new Uri('http://example.com/'),
            new IdentifierCollection([
                $pageElementReferenceIdentifier,
            ])
        );

        $elementParameterIdentifier = (ReferenceIdentifier::createElementReferenceIdentifier(
            new DomIdentifierReference(DomIdentifierReferenceType::ELEMENT, '$elements.element_name', 'element_name')
        ))->withName('name');

        $pageWithElementParameterIdentifier = new Page(
            new Uri('http://example.com/'),
            new IdentifierCollection([
                $elementParameterIdentifier,
            ])
        );

        $attributeIdentifier = (TestIdentifierFactory::createElementIdentifier(
            '.selector',
            null,
            'name'
        ))->withAttributeName('attribute_name');

        $pageWithAttributeIdentifier = new Page(
            new Uri('http://example.com/'),
            new IdentifierCollection([
                $attributeIdentifier,
            ])
        );

        return [
            'empty uri' => [
                'page' => $emptyUriPage,
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
                    new DomIdentifier('.selector'),
                ])),
            ],
        ];
    }
}
