<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Assertion\AssertionComparison;
use webignition\BasilModel\Assertion\AssertionInterface;
use webignition\BasilModelValidator\Identifier\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class AssertionValidator implements ValidatorInterface
{
    const REASON_EXAMINED_VALUE_INVALID  = 'assertion-examined-value-invalid';
    const REASON_EXPECTED_VALUE_INVALID  = 'assertion-expected-value-invalid';

    const REQUIRES_EXPECTED_VALUE_COMPARISONS = [
        AssertionComparison::IS,
        AssertionComparison::IS_NOT,
        AssertionComparison::INCLUDES,
        AssertionComparison::EXCLUDES,
        AssertionComparison::MATCHES,
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

        $examinedValueValidationResult = $this->valueValidator->validate($examinedValue);
        if ($examinedValueValidationResult instanceof InvalidResultInterface) {
            return $this->createInvalidResult(
                $model,
                self::REASON_EXAMINED_VALUE_INVALID,
                $examinedValueValidationResult
            );
        }

        $requiresExpectedValue = in_array($model->getComparison(), self::REQUIRES_EXPECTED_VALUE_COMPARISONS);

        if ($requiresExpectedValue) {
            $expectedValue = $model->getExpectedValue();

            $expectedValueValidationResult = $this->valueValidator->validate($expectedValue);

            if ($expectedValueValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult(
                    $model,
                    self::REASON_EXPECTED_VALUE_INVALID,
                    $expectedValueValidationResult
                );
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
