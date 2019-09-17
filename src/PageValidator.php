<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Identifier\DomIdentifierInterface;
use webignition\BasilModel\Page\PageInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class PageValidator implements ValidatorInterface
{
    const REASON_URL_MISSING = 'page-url-missing';
    const REASON_INVALID_IDENTIFIER_TYPE = 'page-invalid-identifier-type';

    const CONTEXT_IDENTIFIER_NAME = 'identifier';

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

        $identifierCollection = $model->getIdentifierCollection();

        foreach ($identifierCollection as $identifier) {
            if (!$identifier instanceof DomIdentifierInterface ||
                ($identifier instanceof DomIdentifierInterface && null !== $identifier->getAttributeName())) {
                $invalidResult = new InvalidResult($model, TypeInterface::PAGE, self::REASON_INVALID_IDENTIFIER_TYPE);
                $invalidResult = $invalidResult->withContext([
                    self::CONTEXT_IDENTIFIER_NAME => $identifier,
                ]);

                return $invalidResult;
            }
        }

        return new ValidResult($model);
    }
}
