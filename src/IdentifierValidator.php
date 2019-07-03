<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class IdentifierValidator implements ValidatorInterface
{
    const CODE_TYPE_INVALID = 1;
    const CODE_VALUE_MISSING = 2;
    const CODE_INVALID_PARENT_IDENTIFIER = 3;

    const VALID_TYPES = [
        IdentifierTypes::CSS_SELECTOR,
        IdentifierTypes::XPATH_EXPRESSION,
        IdentifierTypes::PAGE_MODEL_ELEMENT_REFERENCE,
        IdentifierTypes::ELEMENT_PARAMETER,
        IdentifierTypes::PAGE_OBJECT_PARAMETER,
        IdentifierTypes::BROWSER_OBJECT_PARAMETER,
    ];

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

        if ('' === $model->getValue()) {
            return $this->createInvalidResult($model, self::CODE_VALUE_MISSING);
        }

        $parentIdentifier = $model->getParentIdentifier();

        if ($parentIdentifier instanceof IdentifierInterface) {
            $parentValidationResult = $this->validate($parentIdentifier);

            if (false === $parentValidationResult->getIsValid()) {
                return $this->createInvalidResult($model, self::CODE_INVALID_PARENT_IDENTIFIER);
            }
        }

        return new ValidResult($model);
    }

    private function createInvalidResult(object $model, int $code): ResultInterface
    {
        return new InvalidResult($model, TypeInterface::IDENTIFIER, $code);
    }
}
