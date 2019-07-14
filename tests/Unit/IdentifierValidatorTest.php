<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelFactory\IdentifierFactory;
use webignition\BasilModelValidator\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidIdentifierResult;
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
        $identifierFactory = IdentifierFactory::createFactory();

        $identifierWithInvalidType = new Identifier('foo', new Value(ValueTypes::STRING, 'value'));
        $identifierWithInvalidValue = new Identifier(IdentifierTypes::CSS_SELECTOR, new Value(ValueTypes::STRING, ''));
        $identifierWithInvalidParent = (new Identifier(
            IdentifierTypes::CSS_SELECTOR,
            new Value(ValueTypes::STRING, '.selector')
        ))
            ->withParentIdentifier($identifierWithInvalidType);

        $identifierWithInvalidPageObjectProperty = $identifierFactory->create('$page.foo');

        $expectedInvalidPageObjectPropertyResult = new InvalidIdentifierResult(
            $identifierWithInvalidPageObjectProperty,
            IdentifierValidator::CODE_INVALID_PAGE_OBJECT_PROPERTY
        );
        $expectedInvalidPageObjectPropertyResult->setPageProperty('foo');

        $identifierWithInvalidBrowserObjectProperty = $identifierFactory->create('$browser.bar');

        $expectedInvalidBrowserObjectPropertyResult = new InvalidIdentifierResult(
            $identifierWithInvalidBrowserObjectProperty,
            IdentifierValidator::CODE_INVALID_BROWSER_OBJECT_PROPERTY
        );
        $expectedInvalidBrowserObjectPropertyResult->setBrowserProperty('bar');

        return [
            'invalid type' => [
                'identifier' => $identifierWithInvalidType,
                'expectedResult' => new InvalidIdentifierResult(
                    $identifierWithInvalidType,
                    IdentifierValidator::CODE_TYPE_INVALID
                ),
            ],
            'invalid value' => [
                'identifier' => $identifierWithInvalidValue,
                'expectedResult' => new InvalidIdentifierResult(
                    $identifierWithInvalidValue,
                    IdentifierValidator::CODE_VALUE_MISSING
                ),
            ],
            'invalid parent identifier' => [
                'identifier' => $identifierWithInvalidParent,
                'expectedResult' => new InvalidIdentifierResult(
                    $identifierWithInvalidParent,
                    IdentifierValidator::CODE_INVALID_PARENT_IDENTIFIER
                ),
            ],
            'invalid page object property' => [
                'identifier' => $identifierWithInvalidPageObjectProperty,
                'expectedResult' => $expectedInvalidPageObjectPropertyResult,
            ],
            'invalid browser object property' => [
                'identifier' => $identifierWithInvalidBrowserObjectProperty,
                'expectedResult' => $expectedInvalidBrowserObjectPropertyResult,
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
            'type: page model element reference' => [
                'identifier' => $identifierFactory->create('page_import_name.elements.element_name'),
            ],
            'type: element parameter' => [
                'identifier' => $identifierFactory->create('$elements.element_name'),
            ],
            'type: page object parameter' => [
                'identifier' => $identifierFactory->create('$page.url'),
            ],
            'type: browser object parameter' => [
                'identifier' => $identifierFactory->create('$browser.title'),
            ],
        ];
    }
}
