<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class ValueValidator implements ValidatorInterface
{
    const CODE_TYPE_INVALID = 1;

    const VALID_TYPES = [
        ValueTypes::STRING,
        ValueTypes::DATA_PARAMETER,
        ValueTypes::ELEMENT_PARAMETER,
    ];

    public function handles(object $model): bool
    {
        return $model instanceof ValueInterface;
    }

    public function validate(object $model): ResultInterface
    {
        if (!$model instanceof ValueInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        if (!in_array($model->getType(), self::VALID_TYPES)) {
            return new InvalidResult($model, TypeInterface::VALUE, self::CODE_TYPE_INVALID);
        }

        return new ValidResult($model);
    }
}
