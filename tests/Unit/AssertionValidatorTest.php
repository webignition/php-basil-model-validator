<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Assertion\Assertion;
use webignition\BasilModel\Assertion\AssertionComparisons;
use webignition\BasilModel\Assertion\AssertionInterface;
use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelValidator\AssertionValidator;
use webignition\BasilModelValidator\IdentifierValidator;
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

        $this->assertionValidator = new AssertionValidator(
            new IdentifierValidator(),
            new ValueValidator()
        );
    }

    public function testHandles()
    {
        $this->assertTrue($this->assertionValidator->handles(\Mockery::mock(AssertionInterface::class)));
        $this->assertFalse($this->assertionValidator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();
        $expectedResult = new InvalidResult($model, TypeInterface::UNHANDLED);

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
        $assertionMissingIdentifier = new Assertion(
            '',
            null,
            null
        );

        $assertionWithInvalidComparison = new Assertion(
            '".selector" foo',
            new Identifier(IdentifierTypes::CSS_SELECTOR, '.selector'),
            'foo'
        );

        $isComparisonMissingValue = new Assertion(
            '".selector" is',
            new Identifier(IdentifierTypes::CSS_SELECTOR, '.selector'),
            AssertionComparisons::IS
        );

        $isNotComparisonMissingValue = new Assertion(
            '".selector" is-not',
            new Identifier(IdentifierTypes::CSS_SELECTOR, '.selector'),
            AssertionComparisons::IS_NOT
        );

        $includesComparisonMissingValue = new Assertion(
            '".selector" includes',
            new Identifier(IdentifierTypes::CSS_SELECTOR, '.selector'),
            AssertionComparisons::INCLUDES
        );

        $excludesComparisonMissingValue = new Assertion(
            '".selector" excludes',
            new Identifier(IdentifierTypes::CSS_SELECTOR, '.selector'),
            AssertionComparisons::EXCLUDES
        );

        $matchesComparisonMissingValue = new Assertion(
            '".selector" matches',
            new Identifier(IdentifierTypes::CSS_SELECTOR, '.selector'),
            AssertionComparisons::MATCHES
        );

        $invalidIdentifier = new Identifier('foo', '');
        $assertionWithInvalidIdentifier = new Assertion(
            'foo',
            $invalidIdentifier,
            'foo'
        );

        $invalidValue = new Value('foo', '');
        $assertionWithInvalidValue = new Assertion(
            '".selector" is "value"',
            new Identifier(IdentifierTypes::CSS_SELECTOR, '.selector'),
            AssertionComparisons::IS,
            $invalidValue
        );

        return [
            'missing identifier' => [
                'assertion' => $assertionMissingIdentifier,
                'expectedResult' => new InvalidResult(
                    $assertionMissingIdentifier,
                    TypeInterface::ASSERTION,
                    AssertionValidator::CODE_IDENTIFIER_MISSING
                ),
            ],
            'invalid comparison' => [
                'assertion' => $assertionWithInvalidComparison,
                'expectedResult' => new InvalidResult(
                    $assertionWithInvalidComparison,
                    TypeInterface::ASSERTION,
                    AssertionValidator::CODE_COMPARISON_INVALID
                ),
            ],
            'is comparison missing value' => [
                'assertion' => $isComparisonMissingValue,
                'expectedResult' => new InvalidResult(
                    $isComparisonMissingValue,
                    TypeInterface::ASSERTION,
                    AssertionValidator::CODE_VALUE_MISSING
                ),
            ],
            'is-not comparison missing value' => [
                'assertion' => $isNotComparisonMissingValue,
                'expectedResult' => new InvalidResult(
                    $isNotComparisonMissingValue,
                    TypeInterface::ASSERTION,
                    AssertionValidator::CODE_VALUE_MISSING
                ),
            ],
            'includes comparison missing value' => [
                'assertion' => $includesComparisonMissingValue,
                'expectedResult' => new InvalidResult(
                    $includesComparisonMissingValue,
                    TypeInterface::ASSERTION,
                    AssertionValidator::CODE_VALUE_MISSING
                ),
            ],
            'excludes comparison missing value' => [
                'assertion' => $excludesComparisonMissingValue,
                'expectedResult' => new InvalidResult(
                    $excludesComparisonMissingValue,
                    TypeInterface::ASSERTION,
                    AssertionValidator::CODE_VALUE_MISSING
                ),
            ],
            'matches comparison missing value' => [
                'assertion' => $matchesComparisonMissingValue,
                'expectedResult' => new InvalidResult(
                    $matchesComparisonMissingValue,
                    TypeInterface::ASSERTION,
                    AssertionValidator::CODE_VALUE_MISSING
                ),
            ],
            'invalid identifier' => [
                'assertion' => $assertionWithInvalidIdentifier,
                'expectedResult' => new InvalidResult(
                    $assertionWithInvalidIdentifier,
                    TypeInterface::ASSERTION,
                    AssertionValidator::CODE_IDENTIFIER_INVALID
                ),
            ],
            'invalid value' => [
                'assertion' => $assertionWithInvalidValue,
                'expectedResult' => new InvalidResult(
                    $assertionWithInvalidValue,
                    TypeInterface::ASSERTION,
                    AssertionValidator::CODE_VALUE_INVALID
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
        return [
            'is comparison' => [
                'assertion' => new Assertion(
                    '".selector" is "value"',
                    new Identifier(
                        IdentifierTypes::CSS_SELECTOR,
                        '.selector'
                    ),
                    AssertionComparisons::IS,
                    new Value(
                        ValueTypes::STRING,
                        'value'
                    )
                ),
            ],
            'is-not comparison' => [
                'assertion' => new Assertion(
                    '".selector" is-not "value"',
                    new Identifier(
                        IdentifierTypes::CSS_SELECTOR,
                        '.selector'
                    ),
                    AssertionComparisons::IS_NOT,
                    new Value(
                        ValueTypes::STRING,
                        'value'
                    )
                ),
            ],
            'exists comparison' => [
                'assertion' => new Assertion(
                    '".selector" exists',
                    new Identifier(
                        IdentifierTypes::CSS_SELECTOR,
                        '.selector'
                    ),
                    AssertionComparisons::EXISTS
                ),
            ],
            'not-exists comparison' => [
                'assertion' => new Assertion(
                    '".selector" not-exists',
                    new Identifier(
                        IdentifierTypes::CSS_SELECTOR,
                        '.selector'
                    ),
                    AssertionComparisons::NOT_EXISTS
                ),
            ],
            'includes comparison' => [
                'assertion' => new Assertion(
                    '".selector" includes "value"',
                    new Identifier(
                        IdentifierTypes::CSS_SELECTOR,
                        '.selector'
                    ),
                    AssertionComparisons::INCLUDES,
                    new Value(
                        ValueTypes::STRING,
                        'value'
                    )
                ),
            ],
            'excludes comparison' => [
                'assertion' => new Assertion(
                    '".selector" excludes "value"',
                    new Identifier(
                        IdentifierTypes::CSS_SELECTOR,
                        '.selector'
                    ),
                    AssertionComparisons::EXCLUDES,
                    new Value(
                        ValueTypes::STRING,
                        'value'
                    )
                ),
            ],
            'matches comparison' => [
                'assertion' => new Assertion(
                    '".selector" matches "value"',
                    new Identifier(
                        IdentifierTypes::CSS_SELECTOR,
                        '.selector'
                    ),
                    AssertionComparisons::MATCHES,
                    new Value(
                        ValueTypes::STRING,
                        'value'
                    )
                ),
            ],
        ];
    }
}
