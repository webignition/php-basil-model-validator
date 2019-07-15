<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Value\ObjectValue;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class ValueValidator implements ValidatorInterface
{
    const CODE_TYPE_INVALID = 1;
    const CODE_PROPERTY_NAME_INVALID = 2;

    const OBJECT_PROPERTY_NAME_WHITELIST = [
        ValueTypes::PAGE_OBJECT_PROPERTY => [
            'url',
            'title',
        ],
        ValueTypes::BROWSER_OBJECT_PROPERTY => [
            'size',
        ],
    ];

    public static function create(): ValueValidator
    {
        return new ValueValidator();
    }

    public function handles(object $model): bool
    {
        return $model instanceof ValueInterface;
    }

    public function validate(object $model): ResultInterface
    {
        if (!$model instanceof ValueInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $type = $model->getType();

        if (!in_array($type, ValueTypes::ALL)) {
            return new InvalidResult($model, TypeInterface::VALUE, self::CODE_TYPE_INVALID);
        }

        if (array_key_exists($type, self::OBJECT_PROPERTY_NAME_WHITELIST) && $model instanceof ObjectValue) {
            $allowedKeys = self::OBJECT_PROPERTY_NAME_WHITELIST[$type];

            if (!in_array($model->getObjectProperty(), $allowedKeys)) {
                return new InvalidResult($model, TypeInterface::VALUE, self::CODE_PROPERTY_NAME_INVALID);
            }
        }

        return new ValidResult($model);
    }
}
