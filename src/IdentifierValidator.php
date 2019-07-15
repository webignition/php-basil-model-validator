<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class IdentifierValidator implements ValidatorInterface
{
    const CODE_TYPE_INVALID = 1;
    const CODE_VALUE_MISSING = 2;
    const CODE_INVALID_PARENT_IDENTIFIER = 3;
    const CODE_VALUE_INVALID = 4;

    const VALID_TYPES = [
        IdentifierTypes::CSS_SELECTOR,
        IdentifierTypes::XPATH_EXPRESSION,
        IdentifierTypes::PAGE_MODEL_ELEMENT_REFERENCE,
        IdentifierTypes::ELEMENT_PARAMETER,
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

    public function validate(object $model): ResultInterface
    {
        if (!$model instanceof IdentifierInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        if (!in_array($model->getType(), self::VALID_TYPES)) {
            return $this->createInvalidResult($model, self::CODE_TYPE_INVALID);
        }

        $value = $model->getValue();

        if ($value->isEmpty()) {
            return $this->createInvalidResult($model, self::CODE_VALUE_MISSING);
        }

        $valueValidationResult = $this->valueValidator->validate($value);
        if ($valueValidationResult instanceof InvalidResultInterface) {
            return $this->createInvalidResult($model, self::CODE_VALUE_INVALID, $valueValidationResult);
        }

        $parentIdentifier = $model->getParentIdentifier();

        if ($parentIdentifier instanceof IdentifierInterface) {
            $parentValidationResult = $this->validate($parentIdentifier);

            if ($parentValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult(
                    $model,
                    self::CODE_INVALID_PARENT_IDENTIFIER,
                    $parentValidationResult
                );
            }
        }

        return new ValidResult($model);
    }

    private function createInvalidResult(
        object $model,
        int $code,
        ?InvalidResultInterface $previous = null
    ): InvalidResultInterface {
        return new InvalidResult($model, TypeInterface::IDENTIFIER, $code, $previous);
    }
}
