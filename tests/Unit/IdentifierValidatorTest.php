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
use webignition\BasilModelFactory\ValueFactory;
use webignition\BasilModelValidator\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidIdentifierResult;
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

        $expectedInvalidPageObjectPropertyResult = new InvalidIdentifierResult(
            $identifierWithInvalidPageObjectProperty,
            IdentifierValidator::CODE_INVALID_PAGE_OBJECT_PROPERTY
        );
        $expectedInvalidPageObjectPropertyResult->setPageProperty('foo');

        return [
            'invalid type' => [
                'identifier' => $identifierWithInvalidType,
                'expectedResult' => new InvalidIdentifierResult(
                    $identifierWithInvalidType,
                    IdentifierValidator::CODE_TYPE_INVALID
                ),
            ],
            'invalid value, empty css selector' => [
                'identifier' => $identifierWithEmptyValue,
                'expectedResult' => new InvalidIdentifierResult(
                    $identifierWithEmptyValue,
                    IdentifierValidator::CODE_VALUE_MISSING
                ),
            ],
            'invalid parent identifier' => [
                'identifier' => $identifierWithInvalidParent,
                'expectedResult' => new InvalidIdentifierResult(
                    $identifierWithInvalidParent,
                    IdentifierValidator::CODE_INVALID_PARENT_IDENTIFIER,
                    new InvalidIdentifierResult(
                        $identifierWithInvalidType,
                        IdentifierValidator::CODE_TYPE_INVALID
                    )
                ),
            ],
            'invalid page object property' => [
                'identifier' => $identifierWithInvalidPageObjectProperty,
                'expectedResult' => new InvalidIdentifierResult(
                    $identifierWithInvalidPageObjectProperty,
                    IdentifierValidator::CODE_VALUE_INVALID,
                    new InvalidResult(
                        $invalidPageObjectValue,
                        TypeInterface::VALUE,
                        ValueValidator::CODE_PROPERTY_NAME_INVALID
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
            'type: page object parameter, url' => [
                'identifier' => $identifierFactory->create('$page.url'),
            ],
            'type: page object parameter, title' => [
                'identifier' => $identifierFactory->create('$page.title'),
            ],
            'type: browser object parameter, size' => [
                'identifier' => $identifierFactory->create('$browser.size'),
            ],
        ];
    }
}
