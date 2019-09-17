<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Value\ObjectValueInterface;
use webignition\BasilModel\Value\ObjectValueType;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModel\Value\WrappedValueInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class ValueValidator implements ValidatorInterface
{
    const REASON_PROPERTY_NAME_INVALID = 'value-property-name-invalid';
    const REASON_UNACTIONABLE = 'value-unactionable';

    const OBJECT_PROPERTY_WHITELIST = [
        ObjectValueType::BROWSER_PROPERTY => [
            'size',
        ],
        ObjectValueType::PAGE_PROPERTY => [
            'title',
            'url',
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
        if ($model instanceof WrappedValueInterface) {
            $model = $model->getWrappedValue();
        }

        if (!$model instanceof ValueInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        if (!$model->isActionable()) {
            return new InvalidResult($model, TypeInterface::VALUE, self::REASON_UNACTIONABLE);
        }

        if ($model instanceof ObjectValueInterface) {
            $propertyWhitelist = self::OBJECT_PROPERTY_WHITELIST[$model->getType()] ?? null;

            if (is_array($propertyWhitelist) && !in_array($model->getProperty(), $propertyWhitelist)) {
                return new InvalidResult($model, TypeInterface::VALUE, self::REASON_PROPERTY_NAME_INVALID);
            }
        }

        return new ValidResult($model);
    }
}
