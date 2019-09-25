<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Result;

use webignition\BasilModel\Identifier\DomIdentifier;
use webignition\BasilModel\Value\ElementExpression;
use webignition\BasilModel\Value\ElementExpressionType;
use webignition\BasilModel\Value\ObjectValue;
use webignition\BasilModel\Value\ObjectValueType;
use webignition\BasilModelFactory\ValueFactory;
use webignition\BasilModelValidator\Identifier\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\ValueValidator;

class InvalidResultTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $model = new DomIdentifier(
            new ElementExpression('.selector', ElementExpressionType::CSS_SELECTOR)
        );

        $type = TypeInterface::IDENTIFIER;
        $reason = IdentifierValidator::REASON_ELEMENT_LOCATOR_MISSING;

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

        $invalidPageObjectPropertyValue = new ObjectValue(ObjectValueType::PAGE_PROPERTY, '$page.foo', 'foo');

        $identifierValidationInvalidResult = new InvalidResult(
            $invalidPageObjectPropertyValue,
            TypeInterface::VALUE,
            ValueValidator::REASON_PROPERTY_NAME_INVALID,
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
