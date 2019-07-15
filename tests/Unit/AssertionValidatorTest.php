<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Assertion\Assertion;
use webignition\BasilModel\Assertion\AssertionComparisons;
use webignition\BasilModel\Assertion\AssertionInterface;
use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelFactory\AssertionFactory;
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

        $valueValidator = new ValueValidator();

        $this->assertionValidator = new AssertionValidator(
            new IdentifierValidator($valueValidator),
            $valueValidator
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

        $assertionMissingIdentifier = $assertionFactory->createFromAssertionString('');
        $assertionWithInvalidComparison = $assertionFactory->createFromAssertionString('".selector" foo');
        $isComparisonMissingValue = $assertionFactory->createFromAssertionString('".selector" is');
        $isNotComparisonMissingValue = $assertionFactory->createFromAssertionString('".selector" is-not');

        $includesComparisonMissingValue = $assertionFactory->createFromAssertionString('".selector" includes');
        $excludesComparisonMissingValue = $assertionFactory->createFromAssertionString('".selector" excludes');
        $matchesComparisonMissingValue = $assertionFactory->createFromAssertionString('".selector" matches');

        $invalidIdentifier = new Identifier('foo', new Value(ValueTypes::STRING, ''));
        $assertionWithInvalidIdentifier = new Assertion(
            'foo',
            $invalidIdentifier,
            'foo'
        );

        $invalidValue = new Value('foo', '');
        $assertionWithInvalidValue = new Assertion(
            '".selector" is "value"',
            new Identifier(IdentifierTypes::CSS_SELECTOR, new Value(ValueTypes::STRING, '.selector')),
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
        $assertionFactory = AssertionFactory::createFactory();

        return [
            'is comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" is "value"'),
            ],
            'is-not comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" is-not "value"'),
            ],
            'exists comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" exists'),
            ],
            'not-exists comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" not-exists'),
            ],
            'includes comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" includes "value"'),
            ],
            'excludes comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" excludes "value"'),
            ],
            'matches comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" matches "value"'),
            ],
        ];
    }
}
