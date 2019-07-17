<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use Nyholm\Psr7\Uri;
use webignition\BasilModel\Page\Page;
use webignition\BasilModel\Page\PageInterface;
use webignition\BasilModelValidator\PageValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class PageValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PageValidator
     */
    private $pageValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pageValidator = PageValidator::create();
    }

    public function testHandles()
    {
        $this->assertTrue($this->pageValidator->handles(\Mockery::mock(PageInterface::class)));
        $this->assertFalse($this->pageValidator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();
        $expectedResult = InvalidResult::createUnhandledModelResult($model);

        $this->assertEquals($expectedResult, $this->pageValidator->validate($model));
    }

    public function testValidateNotValid()
    {
        $page = new Page(new Uri(''), []);
        $expectedResult = new InvalidResult($page, TypeInterface::PAGE, PageValidator::REASON_URL_MISSING);

        $this->assertEquals($expectedResult, $this->pageValidator->validate($page));
    }

    public function testValidateIsValid()
    {
        $page = new Page(new Uri('http://example.com/'), []);

        $this->assertEquals(new ValidResult($page), $this->pageValidator->validate($page));
    }
}
