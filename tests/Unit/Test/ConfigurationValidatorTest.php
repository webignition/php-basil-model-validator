<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Tests\Unit\Test;

use webignition\BasilModel\Test\Configuration;
use webignition\BasilModel\Test\ConfigurationInterface;
use webignition\BasilModelValidator\TypeInterface;
use webignition\BasilModelValidator\Test\ConfigurationValidator;
use webignition\BasilValidationResult\InvalidResult;
use webignition\BasilValidationResult\ResultInterface;
use webignition\BasilValidationResult\ValidResult;

class ConfigurationValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigurationValidator
     */
    private $configurationValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurationValidator = ConfigurationValidator::create();
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(ConfigurationInterface $configuration, ResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->configurationValidator->validate($configuration));
    }

    public function validateNotValidDataProvider(): array
    {
        $configurationWithEmptyBrowser = new Configuration('', '');
        $configurationWithWhitespaceOnlyBrowser = new Configuration('', '');
        $configurationWithPageUrlReferenceUrl = new Configuration('chrome', 'page_import_name.url');

        return [
            'browser empty' => [
                'configuration' => $configurationWithEmptyBrowser,
                'expectedResult' => new InvalidResult(
                    $configurationWithEmptyBrowser,
                    TypeInterface::TEST_CONFIGURATION,
                    ConfigurationValidator::REASON_BROWSER_MISSING
                ),
            ],
            'browser whitespace-only' => [
                'configuration' => $configurationWithWhitespaceOnlyBrowser,
                'expectedResult' => new InvalidResult(
                    $configurationWithWhitespaceOnlyBrowser,
                    TypeInterface::TEST_CONFIGURATION,
                    ConfigurationValidator::REASON_BROWSER_MISSING
                ),
            ],
            'url is page url reference' => [
                'configuration' => $configurationWithPageUrlReferenceUrl,
                'expectedResult' => new InvalidResult(
                    $configurationWithPageUrlReferenceUrl,
                    TypeInterface::TEST_CONFIGURATION,
                    ConfigurationValidator::REASON_URL_IS_PAGE_URL_REFERENCE
                ),
            ],
        ];
    }

    public function testValidateIsValid()
    {
        $configuration = new Configuration('chrome', 'http://example.com/');

        $expectedResult = new ValidResult($configuration);

        $this->assertEquals($expectedResult, $this->configurationValidator->validate($configuration));
    }
}
