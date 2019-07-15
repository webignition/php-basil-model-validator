<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Result;

use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelFactory\ValueFactory;
use webignition\BasilModelValidator\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\ValueValidator;

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

    public function testGetPrevious()
    {
        $valueFactory = ValueFactory::createFactory();

        $invalidValue = $valueFactory->createFromValueString('$page.foo');

        $valueValidationInvalidResult = new InvalidResult(
            $invalidValue,
            TypeInterface::VALUE,
            ValueValidator::CODE_PROPERTY_NAME_INVALID
        );

        $invalidIdentifier = new Identifier(IdentifierTypes::PAGE_OBJECT_PARAMETER, $invalidValue);

        $identifierValidationInvalidResult = new InvalidResult(
            $invalidIdentifier,
            TypeInterface::IDENTIFIER,
            IdentifierValidator::CODE_VALUE_INVALID,
            $valueValidationInvalidResult
        );

        $this->assertSame($valueValidationInvalidResult, $identifierValidationInvalidResult->getPrevious());
    }
}
