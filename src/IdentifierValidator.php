<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Identifier\ElementIdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierTypes;
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
    const REASON_VALUE_TYPE_MISMATCH = 'identifier-value-type-mismatch';

    const TYPES_REQUIRING_STRING_VALUE = [
        IdentifierTypes::ELEMENT_SELECTOR,
    ];

    const TYPES_REQUIRING_OBJECT_VALUE = [
        IdentifierTypes::PAGE_ELEMENT_REFERENCE,
    ];

    public static function create(): IdentifierValidator
    {
        return new IdentifierValidator();
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

        if (!$model instanceof ElementIdentifierInterface) {
            return $this->createInvalidResult($model, self::REASON_TYPE_INVALID);
        }

        $value = $model->getValue();
        if ($value->isEmpty()) {
            return $this->createInvalidResult($model, self::REASON_VALUE_MISSING);
        }

        $valueType = $value->getType();
        if (!in_array($valueType, [ValueTypes::CSS_SELECTOR, ValueTypes::XPATH_EXPRESSION])) {
            return $this->createInvalidResult($model, self::REASON_VALUE_TYPE_MISMATCH);
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
