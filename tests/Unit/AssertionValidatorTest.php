<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Assertion\Assertion;
use webignition\BasilModel\Assertion\AssertionComparisons;
use webignition\BasilModel\Assertion\AssertionInterface;
use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Value\ElementValue;
use webignition\BasilModel\Value\LiteralValue;
use webignition\BasilModelFactory\AssertionFactory;
use webignition\BasilModelFactory\ValueFactory;
use webignition\BasilModelValidator\AssertionValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValueValidator;

class AssertionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AssertionValidator
     */
    private $assertionValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertionValidator = AssertionValidator::create();
    }

    public function testHandles()
    {
        $this->assertTrue($this->assertionValidator->handles(\Mockery::mock(AssertionInterface::class)));
        $this->assertFalse($this->assertionValidator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();
        $expectedResult = InvalidResult::createUnhandledModelResult($model);

        $this->assertEquals($expectedResult, $this->assertionValidator->validate($model));
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(AssertionInterface $assertion, ResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->assertionValidator->validate($assertion));
    }

    public function validateNotValidDataProvider(): array
    {
        $assertionFactory = AssertionFactory::createFactory();
        $valueFactory = ValueFactory::createFactory();

        $emptyAssertion = $assertionFactory->createFromAssertionString('');

        $invalidElementValue = new ElementValue(
            new Identifier(
                IdentifierTypes::PAGE_ELEMENT_REFERENCE,
                LiteralValue::createStringValue('invalid')
            )
        );

        $assertionWithInvalidExaminedValue = new Assertion(
            '',
            $invalidElementValue,
            AssertionComparisons::EXISTS
        );

        $assertionWithInvalidComparison = $assertionFactory->createFromAssertionString('".selector" foo');
        $isComparisonMissingExpectedValue = $assertionFactory->createFromAssertionString('".selector" is');

        $assertionWithInvalidExpectedValue = new Assertion(
            '',
            $valueFactory->createFromValueString('$page.url'),
            AssertionComparisons::IS,
            $invalidElementValue
        );

        $assertionWithElementSelectorLiteralExpectedValue = new Assertion(
            '',
            $valueFactory->createFromValueString('$page.url'),
            AssertionComparisons::IS,
            LiteralValue::createCssSelectorValue('.selector')
        );

        $assertionWithPageElementReferenceExpectedValue = $assertionFactory->createFromAssertionString(
            '$page.url is page_import_name.elements.element_name'
        );

        return [
            'missing examined value' => [
                'assertion' => $emptyAssertion,
                'expectedResult' => new InvalidResult(
                    $emptyAssertion,
                    TypeInterface::ASSERTION,
                    AssertionValidator::REASON_EXAMINED_VALUE_MISSING
                ),
            ],
            'invalid examined value, invalid element value' => [
                'assertion' => $assertionWithInvalidExaminedValue,
                'expectedResult' => new InvalidResult(
                    $assertionWithInvalidExaminedValue,
                    TypeInterface::ASSERTION,
                    AssertionValidator::REASON_EXAMINED_VALUE_INVALID,
                    new InvalidResult(
                        $invalidElementValue,
                        TypeInterface::VALUE,
                        ValueValidator::REASON_ELEMENT_VALUE_IDENTIFIER_INVALID
                    )
                ),
            ],
            'invalid comparison' => [
                'assertion' => $assertionWithInvalidComparison,
                'expectedResult' => new InvalidResult(
                    $assertionWithInvalidComparison,
                    TypeInterface::ASSERTION,
                    AssertionValidator::REASON_COMPARISON_INVALID
                ),
            ],
            'is comparison missing expected value' => [
                'assertion' => $isComparisonMissingExpectedValue,
                'expectedResult' => new InvalidResult(
                    $isComparisonMissingExpectedValue,
                    TypeInterface::ASSERTION,
                    AssertionValidator::REASON_EXPECTED_VALUE_MISSING
                ),
            ],
            'invalid examined value, expected value of correct type but invalid' => [
                'assertion' => $assertionWithInvalidExpectedValue,
                'expectedResult' => new InvalidResult(
                    $assertionWithInvalidExpectedValue,
                    TypeInterface::ASSERTION,
                    AssertionValidator::REASON_EXPECTED_VALUE_INVALID,
                    new InvalidResult(
                        $invalidElementValue,
                        TypeInterface::VALUE,
                        ValueValidator::REASON_ELEMENT_VALUE_IDENTIFIER_INVALID
                    )
                ),
            ],
            'invalid examined value, element selector literal' => [
                'assertion' => $assertionWithElementSelectorLiteralExpectedValue,
                'expectedResult' => new InvalidResult(
                    $assertionWithElementSelectorLiteralExpectedValue,
                    TypeInterface::ASSERTION,
                    AssertionValidator::REASON_EXPECTED_VALUE_INVALID
                ),
            ],
            'invalid examined value, page element reference object value' => [
                'assertion' => $assertionWithPageElementReferenceExpectedValue,
                'expectedResult' => new InvalidResult(
                    $assertionWithPageElementReferenceExpectedValue,
                    TypeInterface::ASSERTION,
                    AssertionValidator::REASON_EXPECTED_VALUE_INVALID
                ),
            ],
        ];
    }

    /**
     * @dataProvider validateIsValidDataProvider
     */
    public function testValidateIsValid(AssertionInterface $assertion)
    {
        $expectedResult = new ValidResult($assertion);

        $this->assertEquals($expectedResult, $this->assertionValidator->validate($assertion));
    }

    public function validateIsValidDataProvider(): array
    {
        $assertionFactory = AssertionFactory::createFactory();

        return [
            'css selector, is comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" is "value"'),
            ],
            'css selector, is-not comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" is-not "value"'),
            ],
            'css selector, exists comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" exists'),
            ],
            'css selector, not-exists comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" not-exists'),
            ],
            'css selector, includes comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" includes "value"'),
            ],
            'css selector, excludes comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" excludes "value"'),
            ],
            'css selector, matches comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" matches "value"'),
            ],
            'browser object property, is comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('$browser.size is "value"'),
            ],
            'data parameter, is comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('$data.key is "value"'),
            ],
            'element parameter, is comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('$elements.element_name is "value"'),
            ],
            'page object property, is comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('$page.url is "value"'),
            ],
            'environment parameter, is comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('$env.KEY is "value"'),
            ],
            'css selector, is comparison, data parameter' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" is $data.key'),
            ],
            'css selector, is comparison, element parameter' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" is $elements.element_name'),
            ],
            'css selector, is comparison, page object property' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" is $page.title'),
            ],
            'css selector, is comparison, environment parameter' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" is $env.KEY'),
            ],
        ];
    }
}
