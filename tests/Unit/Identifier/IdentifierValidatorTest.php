<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Identifier;

use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModelValidator\Identifier\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class IdentifierValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IdentifierValidator
     */
    private $identifierValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identifierValidator = new IdentifierValidator();
    }

    public function testHandles()
    {
        $this->assertTrue($this->identifierValidator->handles(\Mockery::mock(IdentifierInterface::class)));
        $this->assertFalse($this->identifierValidator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();

        $expectedResult = new InvalidResult($model, TypeInterface::UNHANDLED);

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
        $identifierWithInvalidType = new Identifier('foo', 'value');
        $identifierWithInvalidValue = new Identifier(IdentifierTypes::CSS_SELECTOR, '');
        $identifierWithInvalidParent = (new Identifier(IdentifierTypes::CSS_SELECTOR, '.selector'))
            ->withParentIdentifier($identifierWithInvalidType);

        return [
            'invalid type' => [
                'identifier' => $identifierWithInvalidType,
                'expectedResult' => new InvalidResult(
                    $identifierWithInvalidType,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::CODE_TYPE_INVALID
                ),
            ],
            'invalid value' => [
                'identifier' => $identifierWithInvalidValue,
                'expectedResult' => new InvalidResult(
                    $identifierWithInvalidValue,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::CODE_VALUE_MISSING
                ),
            ],
            'invalid parent identifier' => [
                'identifier' => $identifierWithInvalidParent,
                'expectedResult' => new InvalidResult(
                    $identifierWithInvalidParent,
                    TypeInterface::IDENTIFIER,
                    IdentifierValidator::CODE_INVALID_PARENT_IDENTIFIER
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
        $parentIdentifier = new Identifier(IdentifierTypes::CSS_SELECTOR, '.parent');

        return [
            'type: css selector' => [
                'identifier' => new Identifier(IdentifierTypes::CSS_SELECTOR, '.selector'),
            ],
            'type: css selector with parent' => [
                'identifier' => (new Identifier(IdentifierTypes::CSS_SELECTOR, '.selector'))
                    ->withParentIdentifier($parentIdentifier),
            ],
            'type: xpath expression' => [
                'identifier' => new Identifier(IdentifierTypes::XPATH_EXPRESSION, '//h1'),
            ],
            'type: page model element reference' => [
                'identifier' => new Identifier(
                    IdentifierTypes::PAGE_MODEL_ELEMENT_REFERENCE,
                    'page_import_name.elements.element_name'
                ),
            ],
            'type: element parameter' => [
                'identifier' => new Identifier(IdentifierTypes::ELEMENT_PARAMETER, '$elements.element_name'),
            ],
            'type: page object parameter' => [
                'identifier' => new Identifier(IdentifierTypes::PAGE_OBJECT_PARAMETER, '$page.url'),
            ],
            'type: browser object parameter' => [
                'identifier' => new Identifier(IdentifierTypes::BROWSER_OBJECT_PARAMETER, '$browser.title'),
            ],
        ];
    }
}