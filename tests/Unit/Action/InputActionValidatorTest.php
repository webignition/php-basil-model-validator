<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\InputAction;
use webignition\BasilModel\Action\InteractionAction;
use webignition\BasilModel\Action\NoArgumentsAction;
use webignition\BasilModel\Action\UnrecognisedAction;
use webignition\BasilModel\Action\WaitAction;
use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelValidator\Action\InputActionValidator;
use webignition\BasilModelValidator\Action\InvalidResultCode;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class InputActionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InputActionValidator
     */
    private $inputActionValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inputActionValidator = new InputActionValidator();
    }

    /**
     * @dataProvider handlesDataProvider
     */
    public function testHandles(ActionInterface $action, bool $expectedHandles)
    {
        $this->assertSame($expectedHandles, $this->inputActionValidator->handles($action));
    }

    public function handlesDataProvider(): array
    {
        return [
            'input action' => [
                'action' => new InputAction(null, null, ''),
                'expectedHandles' => true,
            ],
            'interaction action' => [
                'action' => new InteractionAction('', null, ''),
                'expectedHandles' => false,
            ],
            'no arguments action' => [
                'action' => new NoArgumentsAction('', ''),
                'expectedHandles' => false,
            ],
            'unrecognised action' => [
                'action' => new UnrecognisedAction('', ''),
                'expectedHandles' => false,
            ],
            'wait action' => [
                'action' => new WaitAction(''),
                'expectedHandles' => false,
            ],
        ];
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(ActionInterface $action, ResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->inputActionValidator->validate($action));
    }

    public function validateNotValidDataProvider(): array
    {
        $inputActionMissingIdentifier = new InputAction(
            null,
            new Value(
                ValueTypes::STRING,
                'foo'
            ),
            ' to "foo"'
        );

        $inputActionMissingValue = new InputAction(
            new Identifier(
                IdentifierTypes::CSS_SELECTOR,
                '.selector'
            ),
            null,
            '".selector" to'
        );

        $inputActionMissingToKeyword = new InputAction(
            new Identifier(
                IdentifierTypes::CSS_SELECTOR,
                '.selector'
            ),
            new Value(
                ValueTypes::STRING,
                'foo'
            ),
            '".selector" "foo"'
        );

        $inputActionWithIdentifierContainingToKeywordMissingToKeyword = new InputAction(
            new Identifier(
                IdentifierTypes::CSS_SELECTOR,
                '.selector to value'
            ),
            new Value(
                ValueTypes::STRING,
                'foo'
            ),
            '".selector to value" "foo"'
        );

        $inputActionWithValueContainingToKeywordMissingToKeyword = new InputAction(
            new Identifier(
                IdentifierTypes::CSS_SELECTOR,
                '.selector'
            ),
            new Value(
                ValueTypes::STRING,
                'foo to value'
            ),
            '".selector" "foo to value"'
        );

        return [
            'input action missing identifier' => [
                'action' => $inputActionMissingIdentifier,
                'expectedResult' => new InvalidResult(
                    $inputActionMissingIdentifier,
                    TypeInterface::ACTION,
                    InvalidResultCode::CODE_INPUT_ACTION_IDENTIFIER_MISSING
                ),
            ],
            'input action missing value' => [
                'action' => $inputActionMissingValue,
                'expectedResult' => new InvalidResult(
                    $inputActionMissingValue,
                    TypeInterface::ACTION,
                    InvalidResultCode::CODE_INPUT_ACTION_VALUE_MISSING
                ),
            ],
            'input action with identifier and value, missing "to" keyword' => [
                'action' => $inputActionMissingToKeyword,
                'expectedResult' => new InvalidResult(
                    $inputActionMissingToKeyword,
                    TypeInterface::ACTION,
                    InvalidResultCode::CODE_INPUT_ACTION_TO_KEYWORD_MISSING
                ),
            ],
            'input action with identifier containing "to" keyword and value, missing "to" keyword' => [
                'action' => $inputActionWithIdentifierContainingToKeywordMissingToKeyword,
                'expectedResult' => new InvalidResult(
                    $inputActionWithIdentifierContainingToKeywordMissingToKeyword,
                    TypeInterface::ACTION,
                    InvalidResultCode::CODE_INPUT_ACTION_TO_KEYWORD_MISSING
                ),
            ],
            'input action with value containing "to" keyword, missing "to" keyword' => [
                'action' => $inputActionWithValueContainingToKeywordMissingToKeyword,
                'expectedResult' => new InvalidResult(
                    $inputActionWithValueContainingToKeywordMissingToKeyword,
                    TypeInterface::ACTION,
                    InvalidResultCode::CODE_INPUT_ACTION_TO_KEYWORD_MISSING
                ),
            ],
        ];
    }

    public function testValidateIsValid()
    {
        $action = new InputAction(
            new Identifier(
                IdentifierTypes::CSS_SELECTOR,
                '.selector'
            ),
            new Value(
                ValueTypes::STRING,
                'foo'
            ),
            '".selector" to "foo"'
        );

        $expectedResult = new ValidResult($action);

        $this->assertEquals($expectedResult, $this->inputActionValidator->validate($action));
    }
}
