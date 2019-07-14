<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Result;

use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelValidator\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidIdentifierResult;
use webignition\BasilModelValidator\Result\TypeInterface;

class InvalidIdentifierResultTest extends \PHPUnit\Framework\TestCase
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

        $code = IdentifierValidator::CODE_VALUE_MISSING;
        $message = '';

        $result = new InvalidIdentifierResult($model, $code);

        $this->assertFalse($result->getIsValid());
        $this->assertSame($model, $result->getModel());
        $this->assertEquals(TypeInterface::IDENTIFIER, $result->getType());
        $this->assertEquals($code, $result->getCode());
        $this->assertEquals($message, $result->getMessage());

        $this->assertEquals('', $result->getPageProperty());
        $this->assertEquals('', $result->getBrowserProperty());
    }
}
