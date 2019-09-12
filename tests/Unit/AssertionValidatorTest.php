<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\Assertion\AssertionInterface;
use webignition\BasilModel\Assertion\ComparisonAssertion;
use webignition\BasilModel\Assertion\ExaminationAssertion;
use webignition\BasilModel\Value\AssertionExaminedValue;
use webignition\BasilModel\Value\AssertionExpectedValue;
use webignition\BasilModel\Value\PageProperty;
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
        $valueFactory = ValueFactory::createFactory();
        $assertionFactory = AssertionFactory::createFactory();

        $invalidValue = $valueFactory->createFromValueString('$page.foo');
        $assertionWithInvalidExaminedValue = $assertionFactory->createFromAssertionString('$page.foo exists');
        $assertionWithInvalidExpectedValue = $assertionFactory->createFromAssertionString('$page.url is $page.foo');

        $examinationAssertionWithInvalidComparison = new ExaminationAssertion(
            '$page.url foo',
            new AssertionExaminedValue(
                new PageProperty('$page.url', 'url')
            ),
            'foo'
        );

        $comparisonAssertionWithInvalidComparison = new ComparisonAssertion(
            '$page.url foo $page.url',
            new AssertionExaminedValue(
                new PageProperty('$page.url', 'url')
            ),
            'foo',
            new AssertionExpectedValue(
                new PageProperty('$page.url', 'url')
            )
        );

        return [
            'invalid examined value, invalid element value' => [
                'assertion' => $assertionWithInvalidExaminedValue,
                'expectedResult' => new InvalidResult(
                    $assertionWithInvalidExaminedValue,
                    TypeInterface::ASSERTION,
                    AssertionValidator::REASON_EXAMINED_VALUE_INVALID,
                    new InvalidResult(
                        $invalidValue,
                        TypeInterface::VALUE,
                        ValueValidator::REASON_PROPERTY_NAME_INVALID
                    )
                ),
            ],
            'invalid expected value, expected value of correct type but invalid' => [
                'assertion' => $assertionWithInvalidExpectedValue,
                'expectedResult' => new InvalidResult(
                    $assertionWithInvalidExpectedValue,
                    TypeInterface::ASSERTION,
                    AssertionValidator::REASON_EXPECTED_VALUE_INVALID,
                    new InvalidResult(
                        $invalidValue,
                        TypeInterface::VALUE,
                        ValueValidator::REASON_PROPERTY_NAME_INVALID
                    )
                ),
            ],
            'examination assertion with invalid comparison' => [
                'assertion' => $examinationAssertionWithInvalidComparison,
                'expectedResult' => new InvalidResult(
                    $examinationAssertionWithInvalidComparison,
                    TypeInterface::ASSERTION,
                    AssertionValidator::REASON_COMPARISON_INVALID
                ),
            ],
            'comparison assertion with invalid comparison' => [
                'assertion' => $comparisonAssertionWithInvalidComparison,
                'expectedResult' => new InvalidResult(
                    $comparisonAssertionWithInvalidComparison,
                    TypeInterface::ASSERTION,
                    AssertionValidator::REASON_COMPARISON_INVALID
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
            'css element selector, is comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" is "value"'),
            ],
            'css element selector, is-not comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" is-not "value"'),
            ],
            'css element selector, exists comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" exists'),
            ],
            'css element selector, not-exists comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" not-exists'),
            ],
            'css element selector, includes comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" includes "value"'),
            ],
            'css element selector, excludes comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" excludes "value"'),
            ],
            'css element selector, matches comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" matches "value"'),
            ],
            'browser object property, is comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('$browser.size is "value"'),
            ],
            'data parameter, is comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('$data.key is "value"'),
            ],
            'page object property, is comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('$page.url is "value"'),
            ],
            'environment parameter, is comparison' => [
                'assertion' => $assertionFactory->createFromAssertionString('$env.KEY is "value"'),
            ],
            'css element selector, is comparison, data parameter' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" is $data.key'),
            ],
            'css element selector, is comparison, page object property' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" is $page.title'),
            ],
            'css element selector, is comparison, environment parameter' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector" is $env.KEY'),
            ],
            'css attribute selector, is comparison, scalar value' => [
                'assertion' => $assertionFactory->createFromAssertionString('".selector".attribute_name is "value"'),
            ],
        ];
    }
}
