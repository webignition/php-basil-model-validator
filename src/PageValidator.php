<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Identifier\DomIdentifierInterface;
use webignition\BasilModel\Page\PageInterface;
use webignition\BasilValidationResult\InvalidResult;
use webignition\BasilValidationResult\ResultInterface;
use webignition\BasilValidationResult\ValidResult;

class PageValidator
{
    public const REASON_URL_MISSING = 'page-url-missing';
    public const REASON_INVALID_IDENTIFIER_TYPE = 'page-invalid-identifier-type';

    public const CONTEXT_IDENTIFIER_NAME = 'identifier';

    public static function create(): PageValidator
    {
        return new PageValidator();
    }

    public function validate(PageInterface $page): ResultInterface
    {
        if ('' === (string) $page->getUri()) {
            return new InvalidResult($page, TypeInterface::PAGE, self::REASON_URL_MISSING);
        }

        $identifierCollection = $page->getIdentifierCollection();

        foreach ($identifierCollection as $identifier) {
            if (
                !$identifier instanceof DomIdentifierInterface ||
                ($identifier instanceof DomIdentifierInterface && null !== $identifier->getAttributeName())
            ) {
                $invalidResult = new InvalidResult($page, TypeInterface::PAGE, self::REASON_INVALID_IDENTIFIER_TYPE);
                $invalidResult = $invalidResult->withContext([
                    self::CONTEXT_IDENTIFIER_NAME => $identifier,
                ]);

                return $invalidResult;
            }
        }

        return new ValidResult($page);
    }
}
