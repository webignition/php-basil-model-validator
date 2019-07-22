<?php

namespace webignition\BasilModelValidator\Test;

use webignition\BasilModel\PageUrlReference\PageUrlReference;
use webignition\BasilModel\Test\ConfigurationInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValidatorInterface;

class ConfigurationValidator implements ValidatorInterface
{
    const REASON_BROWSER_MISSING = 'test-configuration-browser-missing';
    const REASON_URL_IS_PAGE_URL_REFERENCE = 'test-configuration-url-is-page-url-reference';

    public static function create(): ConfigurationValidator
    {
        return new ConfigurationValidator();
    }

    public function handles(object $model): bool
    {
        return $model instanceof ConfigurationInterface;
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof ConfigurationInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $browser = $model->getBrowser();

        if ('' === trim($browser)) {
            return $this->createInvalidResult($model, self::REASON_BROWSER_MISSING);
        }

        $url = $model->getUrl();
        $pageUrlReference = new PageUrlReference($url);

        if ($pageUrlReference->isValid()) {
            return $this->createInvalidResult($model, self::REASON_URL_IS_PAGE_URL_REFERENCE);
        }

        return new ValidResult($model);
    }

    private function createInvalidResult(
        object $model,
        string $reason,
        ?InvalidResultInterface $invalidResult = null
    ): ResultInterface {
        return new InvalidResult($model, TypeInterface::TEST_CONFIGURATION, $reason, $invalidResult);
    }
}
