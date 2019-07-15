<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Result;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class ValidResultTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $model = \Mockery::mock(ActionInterface::class);

        $result = new ValidResult($model);

        $this->assertTrue($result->getIsValid());
        $this->assertSame($model, $result->getModel());
    }
}
