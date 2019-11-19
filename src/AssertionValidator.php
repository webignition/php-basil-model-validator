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

class AssertionValidator implements ValidatorInterface
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

    public function handles(object $model): bool
    {
        return $model instanceof AssertionInterface;
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof AssertionInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        if ($model instanceof ExaminationAssertionInterface) {
            $examinedValue = $model->getExaminedValue();

            $examinedValueValidationResult = $this->valueValidator->validate($examinedValue);
            if ($examinedValueValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult(
                    $model,
                    self::REASON_EXAMINED_VALUE_INVALID,
                    $examinedValueValidationResult
                );
            }

            if (!$model instanceof ComparisonAssertionInterface) {
                if (!in_array($model->getComparison(), AssertionComparison::EXAMINATION_COMPARISONS)) {
                    return $this->createInvalidResult($model, self::REASON_COMPARISON_INVALID);
                }
            }
        }

        if ($model instanceof ComparisonAssertionInterface) {
            $expectedValue = $model->getExpectedValue();

            $expectedValueValidationResult = $this->valueValidator->validate($expectedValue);

            if ($expectedValueValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult(
                    $model,
                    self::REASON_EXPECTED_VALUE_INVALID,
                    $expectedValueValidationResult
                );
            }

            if (!in_array($model->getComparison(), AssertionComparison::COMPARISON_COMPARISONS)) {
                return $this->createInvalidResult($model, self::REASON_COMPARISON_INVALID);
            }
        }

        return new ValidResult($model);
    }

    private function createInvalidResult(
        object $model,
        string $reason,
        ?InvalidResultInterface $invalidResult = null
    ): ResultInterface {
        return new InvalidResult($model, TypeInterface::ASSERTION, $reason, $invalidResult);
    }
}
