<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Identifier\ElementIdentifierInterface;
use webignition\BasilModel\Value\ElementValueInterface;
use webignition\BasilModel\Value\ObjectValue;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class ValueValidator implements ValidatorInterface
{
    const REASON_TYPE_INVALID = 'value-type-invalid';
    const REASON_PROPERTY_NAME_INVALID = 'value-property-name-invalid';
    const REASON_ELEMENT_VALUE_IDENTIFIER_INVALID = 'value-element-value-identifier-invalid';

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

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof ValueInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $type = $model->getType();

        if (!in_array($type, ValueTypes::ALL)) {
            return new InvalidResult($model, TypeInterface::VALUE, self::REASON_TYPE_INVALID);
        }

        if (array_key_exists($type, self::OBJECT_PROPERTY_NAME_WHITELIST) && $model instanceof ObjectValue) {
            $allowedKeys = self::OBJECT_PROPERTY_NAME_WHITELIST[$type];

            if (!in_array($model->getObjectProperty(), $allowedKeys)) {
                return new InvalidResult($model, TypeInterface::VALUE, self::REASON_PROPERTY_NAME_INVALID);
            }
        }

        if ($model instanceof ElementValueInterface && !$model->getIdentifier() instanceof ElementIdentifierInterface) {
            return new InvalidResult($model, TypeInterface::VALUE, self::REASON_ELEMENT_VALUE_IDENTIFIER_INVALID);
        }

        return new ValidResult($model);
    }
}
