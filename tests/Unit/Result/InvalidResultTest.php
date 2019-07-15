<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Result;

use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelValidator\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\TypeInterface;

class InvalidResultTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $model = new Identifier(
            IdentifierTypes::CSS_SELECTOR,
            new Value(
                ValueTypes::STRING,
                ''
            )
        );

        $type = TypeInterface::IDENTIFIER;
        $code = IdentifierValidator::CODE_VALUE_MISSING;

        $result = new InvalidResult($model, $type, $code);

        $this->assertFalse($result->getIsValid());
        $this->assertSame($model, $result->getModel());
        $this->assertEquals($type, $result->getType());
        $this->assertEquals($code, $result->getCode());
    }

    public function testCreateUnhandledModelResult()
    {
        $model = new \stdClass();

        $result = InvalidResult::createUnhandledModelResult($model);

        $this->assertFalse($result->getIsValid());
        $this->assertSame($model, $result->getModel());
        $this->assertSame(TypeInterface::UNHANDLED, $result->getType());
        $this->assertSame(0, $result->getCode());
    }
}
