<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Value\ObjectValueInterface;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class IdentifierValidator implements ValidatorInterface
{
    const REASON_TYPE_INVALID = 'identifier-type-invalid';
    const REASON_VALUE_MISSING = 'identifier-value-missing';
    const REASON_INVALID_PARENT_IDENTIFIER = 'identifier-invalid-parent-identifier';
    const REASON_VALUE_INVALID = 'identifier-value-invalid';
    const REASON_TYPE_MISMATCH = 'identifier-type-mismatch';

    const VALID_TYPES = [
        IdentifierTypes::CSS_SELECTOR,
        IdentifierTypes::XPATH_EXPRESSION,
        IdentifierTypes::PAGE_OBJECT_PARAMETER,
        IdentifierTypes::BROWSER_OBJECT_PARAMETER,
        IdentifierTypes::ELEMENT_PARAMETER,
    ];

    const TYPES_REQUIRING_STRING_VALUE = [
        IdentifierTypes::CSS_SELECTOR,
        IdentifierTypes::XPATH_EXPRESSION,
    ];

    const TYPES_REQUIRING_OBJECT_VALUE = [
        IdentifierTypes::PAGE_OBJECT_PARAMETER,
        IdentifierTypes::BROWSER_OBJECT_PARAMETER,
    ];

    private $valueValidator;

    public function __construct(ValueValidator $valueValidator)
    {
        $this->valueValidator = $valueValidator;
    }

    public static function create(): IdentifierValidator
    {
        return new IdentifierValidator(
            ValueValidator::create()
        );
    }

    public function handles(object $model): bool
    {
        return $model instanceof IdentifierInterface;
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof IdentifierInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        if (!in_array($model->getType(), self::VALID_TYPES)) {
            return $this->createInvalidResult($model, self::REASON_TYPE_INVALID);
        }

        $value = $model->getValue();

        if ($value->isEmpty()) {
            return $this->createInvalidResult($model, self::REASON_VALUE_MISSING);
        }

        $valueValidationResult = $this->valueValidator->validate($value);
        if ($valueValidationResult instanceof InvalidResultInterface) {
            return $this->createInvalidResult($model, self::REASON_VALUE_INVALID, $valueValidationResult);
        }

        $type = $model->getType();

        if (in_array($type, self::TYPES_REQUIRING_STRING_VALUE)) {
            if (ValueTypes::STRING !== $value->getType()) {
                return $this->createInvalidResult($model, self::REASON_TYPE_MISMATCH);
            }
        }

        if (in_array($type, self::TYPES_REQUIRING_OBJECT_VALUE)) {
            if (!$value instanceof ObjectValueInterface) {
                return $this->createInvalidResult($model, self::REASON_TYPE_MISMATCH);
            }

            if ($value->getType() === ValueTypes::PAGE_OBJECT_PROPERTY && $value->getObjectName() !== 'page') {
                return $this->createInvalidResult($model, self::REASON_TYPE_MISMATCH);
            }

            if ($value->getType() === ValueTypes::BROWSER_OBJECT_PROPERTY && $value->getObjectName() !== 'browser') {
                return $this->createInvalidResult($model, self::REASON_TYPE_MISMATCH);
            }
        }

        $parentIdentifier = $model->getParentIdentifier();

        if ($parentIdentifier instanceof IdentifierInterface) {
            $parentValidationResult = $this->validate($parentIdentifier);

            if ($parentValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult(
                    $model,
                    self::REASON_INVALID_PARENT_IDENTIFIER,
                    $parentValidationResult
                );
            }
        }

        return new ValidResult($model);
    }

    private function createInvalidResult(
        object $model,
        string $reason,
        ?InvalidResultInterface $previous = null
    ): InvalidResultInterface {
        return new InvalidResult($model, TypeInterface::IDENTIFIER, $reason, $previous);
    }
}
