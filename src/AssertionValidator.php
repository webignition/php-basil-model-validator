<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Assertion\AssertionComparisons;
use webignition\BasilModel\Assertion\AssertionInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class AssertionValidator implements ValidatorInterface
{
    const CODE_IDENTIFIER_MISSING = 1;
    const CODE_COMPARISON_INVALID = 2;
    const CODE_VALUE_MISSING = 3;
    const CODE_IDENTIFIER_INVALID = 4;
    const CODE_VALUE_INVALID = 5;

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

    public function handles(object $model): bool
    {
        return $model instanceof AssertionInterface;
    }

    public function validate(object $model): ResultInterface
    {
        if (!$model instanceof AssertionInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        if (null === $model->getIdentifier()) {
            return $this->createInvalidResult($model, self::CODE_IDENTIFIER_MISSING);
        }

        $identifierValidationResult = $this->identifierValidator->validate($model->getIdentifier());
        if (false === $identifierValidationResult->getIsValid()) {
            return $this->createInvalidResult($model, self::CODE_IDENTIFIER_INVALID);
        }

        if (!in_array($model->getComparison(), self::VALID_COMPARISONS)) {
            return $this->createInvalidResult($model, self::CODE_COMPARISON_INVALID);
        }

        $requiresValue = in_array($model->getComparison(), self::REQUIRES_VALUE_COMPARISONS);

        if ($requiresValue) {
            if (null === $model->getValue()) {
                return $this->createInvalidResult($model, self::CODE_VALUE_MISSING);
            }

            $valueValidationResult = $this->valueValidator->validate($model->getValue());

            if (false === $valueValidationResult->getIsValid()) {
                return $this->createInvalidResult($model, self::CODE_VALUE_INVALID);
            }
        }

        return new ValidResult($model);
    }

    private function createInvalidResult(object $model, int $code): ResultInterface
    {
        return new InvalidResult($model, TypeInterface::ASSERTION, $code);
    }
}
