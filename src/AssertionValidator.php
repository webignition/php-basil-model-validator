<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Assertion\AssertionComparisons;
use webignition\BasilModel\Assertion\AssertionInterface;
use webignition\BasilModel\Value\LiteralValueInterface;
use webignition\BasilModel\Value\ObjectValueInterface;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class AssertionValidator implements ValidatorInterface
{
    const REASON_EXAMINED_VALUE_MISSING = 'assertion-examined-value-missing';
    const REASON_EXAMINED_VALUE_INVALID  = 'assertion-examined-value-invalid';
    const REASON_COMPARISON_INVALID = 'assertion-comparison-missing';
    const REASON_EXPECTED_VALUE_MISSING = 'assertion-expected-value-missing';
    const REASON_EXPECTED_VALUE_INVALID  = 'assertion-expected-value-invalid';

    const VALID_COMPARISONS = AssertionComparisons::ALL;

    const REQUIRES_EXPECTED_VALUE_COMPARISONS = [
        AssertionComparisons::IS,
        AssertionComparisons::IS_NOT,
        AssertionComparisons::INCLUDES,
        AssertionComparisons::EXCLUDES,
        AssertionComparisons::MATCHES,
    ];

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

        $examinedValue = $model->getExaminedValue();
        if (null === $examinedValue) {
            return $this->createInvalidResult($model, self::REASON_EXAMINED_VALUE_MISSING);
        }

        $examinedValueValidationResult = $this->valueValidator->validate($examinedValue);
        if ($examinedValueValidationResult instanceof InvalidResultInterface) {
            return $this->createInvalidResult(
                $model,
                self::REASON_EXAMINED_VALUE_INVALID,
                $examinedValueValidationResult
            );
        }

        if (!$this->isValueValid($examinedValue)) {
            return $this->createInvalidResult($model, self::REASON_EXAMINED_VALUE_INVALID);
        }

        if (!in_array($model->getComparison(), self::VALID_COMPARISONS)) {
            return $this->createInvalidResult($model, self::REASON_COMPARISON_INVALID);
        }

        $requiresExpectedValue = in_array($model->getComparison(), self::REQUIRES_EXPECTED_VALUE_COMPARISONS);

        if ($requiresExpectedValue) {
            $expectedValue = $model->getExpectedValue();

            if (null === $expectedValue) {
                return $this->createInvalidResult($model, self::REASON_EXPECTED_VALUE_MISSING);
            }

            $expectedValueValidationResult = $this->valueValidator->validate($expectedValue);

            if ($expectedValueValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult(
                    $model,
                    self::REASON_EXPECTED_VALUE_INVALID,
                    $expectedValueValidationResult
                );
            }

            if (!$this->isValueValid($expectedValue)) {
                return $this->createInvalidResult($model, self::REASON_EXPECTED_VALUE_INVALID);
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

    private function isValueValid(ValueInterface $value): bool
    {
        $expectedValueType = $value->getType();

        if ($value instanceof LiteralValueInterface && $expectedValueType !== ValueTypes::STRING) {
            return false;
        }

        if ($value instanceof ObjectValueInterface) {
            return in_array(
                $expectedValueType,
                [
                    ValueTypes::BROWSER_OBJECT_PROPERTY,
                    ValueTypes::DATA_PARAMETER,
                    ValueTypes::ELEMENT_PARAMETER,
                    ValueTypes::ENVIRONMENT_PARAMETER,
                    ValueTypes::PAGE_OBJECT_PROPERTY,
                ]
            );
        }

        return true;
    }
}
