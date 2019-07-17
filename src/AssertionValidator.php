<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Assertion\AssertionComparisons;
use webignition\BasilModel\Assertion\AssertionInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class AssertionValidator implements ValidatorInterface
{
    const REASON_IDENTIFIER_MISSING = 'assertion-identifier-missing';
    const REASON_COMPARISON_INVALID = 'assertion-comparison-missing';
    const REASON_VALUE_MISSING = 'assertion-value-missing';
    const REASON_IDENTIFIER_INVALID = 'assertion-identifier-invalid';
    const REASON_VALUE_INVALID = 'assertion-value-invalid';

    const VALID_COMPARISONS = AssertionComparisons::ALL;

    const REQUIRES_VALUE_COMPARISONS = [
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

        if (null === $model->getIdentifier()) {
            return $this->createInvalidResult($model, self::REASON_IDENTIFIER_MISSING);
        }

        $identifierValidationResult = $this->identifierValidator->validate($model->getIdentifier());
        if (false === $identifierValidationResult->getIsValid()) {
            return $this->createInvalidResult($model, self::REASON_IDENTIFIER_INVALID);
        }

        if (!in_array($model->getComparison(), self::VALID_COMPARISONS)) {
            return $this->createInvalidResult($model, self::REASON_COMPARISON_INVALID);
        }

        $requiresValue = in_array($model->getComparison(), self::REQUIRES_VALUE_COMPARISONS);

        if ($requiresValue) {
            if (null === $model->getValue()) {
                return $this->createInvalidResult($model, self::REASON_VALUE_MISSING);
            }

            $valueValidationResult = $this->valueValidator->validate($model->getValue());

            if ($valueValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult($model, self::REASON_VALUE_INVALID, $valueValidationResult);
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
