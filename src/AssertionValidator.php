<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Assertion\AssertionComparison;
use webignition\BasilModel\Assertion\AssertionInterface;
use webignition\BasilModel\Assertion\ComparisonAssertionInterface;
use webignition\BasilModel\Assertion\ExaminationAssertionInterface;
use webignition\BasilModelValidator\Identifier\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class AssertionValidator
{
    public const REASON_EXAMINED_VALUE_INVALID  = 'assertion-examined-value-invalid';
    public const REASON_EXPECTED_VALUE_INVALID  = 'assertion-expected-value-invalid';
    public const REASON_COMPARISON_INVALID = 'assertion-comparison-invalid';

    private $identifierValidator;
    private $valueValidator;

    public function __construct(IdentifierValidator $identifierValidator, ValueValidator $valueValidator)
    {
        $this->identifierValidator = $identifierValidator;
        $this->valueValidator = $valueValidator;
    }

    public static function create(): AssertionValidator
    {
        return new AssertionValidator(
            IdentifierValidator::create(),
            ValueValidator::create()
        );
    }

    public function validate(AssertionInterface $assertion): ResultInterface
    {
        if ($assertion instanceof ExaminationAssertionInterface) {
            $examinedValue = $assertion->getExaminedValue();

            $examinedValueValidationResult = $this->valueValidator->validate($examinedValue);
            if ($examinedValueValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult(
                    $assertion,
                    self::REASON_EXAMINED_VALUE_INVALID,
                    $examinedValueValidationResult
                );
            }

            if (!$assertion instanceof ComparisonAssertionInterface) {
                if (!in_array($assertion->getComparison(), AssertionComparison::EXAMINATION_COMPARISONS)) {
                    return $this->createInvalidResult($assertion, self::REASON_COMPARISON_INVALID);
                }
            }
        }

        if ($assertion instanceof ComparisonAssertionInterface) {
            $expectedValue = $assertion->getExpectedValue();

            $expectedValueValidationResult = $this->valueValidator->validate($expectedValue);

            if ($expectedValueValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult(
                    $assertion,
                    self::REASON_EXPECTED_VALUE_INVALID,
                    $expectedValueValidationResult
                );
            }

            if (!in_array($assertion->getComparison(), AssertionComparison::COMPARISON_COMPARISONS)) {
                return $this->createInvalidResult($assertion, self::REASON_COMPARISON_INVALID);
            }
        }

        return new ValidResult($assertion);
    }

    private function createInvalidResult(
        object $model,
        string $reason,
        ?InvalidResultInterface $invalidResult = null
    ): ResultInterface {
        return new InvalidResult($model, TypeInterface::ASSERTION, $reason, $invalidResult);
    }
}
