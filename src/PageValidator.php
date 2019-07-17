<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Page\PageInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class PageValidator implements ValidatorInterface
{
    const REASON_URL_MISSING = 'page-url-missing';

    public static function create(): PageValidator
    {
        return new PageValidator();
    }

    public function handles(object $model): bool
    {
        return $model instanceof PageInterface;
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof PageInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        if ('' === (string) $model->getUri()) {
            return new InvalidResult($model, TypeInterface::PAGE, self::REASON_URL_MISSING);
        }

        return new ValidResult($model);
    }
}
