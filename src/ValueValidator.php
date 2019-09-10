<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Value\BrowserProperty;
use webignition\BasilModel\Value\PageProperty;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class ValueValidator implements ValidatorInterface
{
    const REASON_PROPERTY_NAME_INVALID = 'value-property-name-invalid';
    const REASON_UNACTIONABLE = 'value-unactionable';

    const BROWSER_PROPERTY_WHITELIST = [
        'size',
    ];

    const PAGE_PROPERTY_WHITELIST = [
        'title',
        'url',
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

        if (!$model->isActionable()) {
            return new InvalidResult($model, TypeInterface::VALUE, self::REASON_UNACTIONABLE);
        }

        if ($model instanceof BrowserProperty && !in_array($model->getProperty(), self::BROWSER_PROPERTY_WHITELIST)) {
            return new InvalidResult($model, TypeInterface::VALUE, self::REASON_PROPERTY_NAME_INVALID);
        }

        if ($model instanceof PageProperty && !in_array($model->getProperty(), self::PAGE_PROPERTY_WHITELIST)) {
            return new InvalidResult($model, TypeInterface::VALUE, self::REASON_PROPERTY_NAME_INVALID);
        }

        return new ValidResult($model);
    }
}
