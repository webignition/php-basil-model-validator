<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Tests\Unit\Test;

use webignition\BasilModel\Test\Configuration;
use webignition\BasilModel\Test\ConfigurationInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\Test\ConfigurationValidator;

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

    public function testHandles()
    {
        $this->assertTrue($this->configurationValidator->handles(\Mockery::mock(ConfigurationInterface::class)));
        $this->assertFalse($this->configurationValidator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();
        $expectedResult = InvalidResult::createUnhandledModelResult($model);

        $this->assertEquals($expectedResult, $this->configurationValidator->validate($model));
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
