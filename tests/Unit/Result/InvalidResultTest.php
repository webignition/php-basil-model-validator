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
        $reason = IdentifierValidator::REASON_VALUE_MISSING;

        $result = new InvalidResult($model, $type, $reason);

        $this->assertFalse($result->getIsValid());
        $this->assertSame($model, $result->getModel());
        $this->assertEquals($type, $result->getType());
        $this->assertEquals($reason, $result->getReason());
    }

    public function testCreateUnhandledModelResult()
    {
        $model = new \stdClass();

        $result = InvalidResult::createUnhandledModelResult($model);

        $this->assertFalse($result->getIsValid());
        $this->assertSame($model, $result->getModel());
        $this->assertSame(TypeInterface::UNHANDLED, $result->getType());
        $this->assertSame('', $result->getReason());
    }

    public function testGetPrevious()
    {
        $valueFactory = ValueFactory::createFactory();

        $invalidValue = $valueFactory->createFromValueString('$page.foo');

        $valueValidationInvalidResult = new InvalidResult(
            $invalidValue,
            TypeInterface::VALUE,
            ValueValidator::REASON_PROPERTY_NAME_INVALID
        );

        $invalidIdentifier = new Identifier(IdentifierTypes::PAGE_OBJECT_PARAMETER, $invalidValue);

        $identifierValidationInvalidResult = new InvalidResult(
            $invalidIdentifier,
            TypeInterface::IDENTIFIER,
            IdentifierValidator::REASON_VALUE_INVALID,
            $valueValidationInvalidResult
        );

        $this->assertSame($valueValidationInvalidResult, $identifierValidationInvalidResult->getPrevious());
    }

    public function testGetContext()
    {
        $context = [
            'foo' => 'bar',
        ];

        $invalidResult = new InvalidResult(new \stdClass(), TypeInterface::NOT_APPLICABLE, 'reason');
        $invalidResult = $invalidResult->withContext($context);

        $this->assertSame($context, $invalidResult->getContext());
    }
}
