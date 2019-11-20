<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Test;

use webignition\BasilModel\PageUrlReference\PageUrlReference;
use webignition\BasilModel\Test\ConfigurationInterface;
use webignition\BasilModelValidator\TypeInterface;
use webignition\BasilValidationResult\InvalidResult;
use webignition\BasilValidationResult\InvalidResultInterface;
use webignition\BasilValidationResult\ResultInterface;
use webignition\BasilValidationResult\ValidResult;

class ConfigurationValidator
{
    public const REASON_BROWSER_MISSING = 'test-configuration-browser-missing';
    public const REASON_URL_IS_PAGE_URL_REFERENCE = 'test-configuration-url-is-page-url-reference';

    public static function create(): ConfigurationValidator
    {
        return new ConfigurationValidator();
    }

    public function validate(ConfigurationInterface $configuration): ResultInterface
    {
        $browser = $configuration->getBrowser();

        if ('' === trim($browser)) {
            return $this->createInvalidResult($configuration, self::REASON_BROWSER_MISSING);
        }

        $url = $configuration->getUrl();
        $pageUrlReference = new PageUrlReference($url);

        if ($pageUrlReference->isValid()) {
            return $this->createInvalidResult($configuration, self::REASON_URL_IS_PAGE_URL_REFERENCE);
        }

        return new ValidResult($configuration);
    }

    private function createInvalidResult(
        ConfigurationInterface $configuration,
        string $reason,
        ?InvalidResultInterface $invalidResult = null
    ): ResultInterface {
        return new InvalidResult($configuration, TypeInterface::TEST_CONFIGURATION, $reason, $invalidResult);
    }
}
