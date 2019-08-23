<?php

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\InputActionInterface;
use webignition\BasilModel\Identifier\AttributeIdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModelValidator\Identifier\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValidatorInterface;
use webignition\BasilModelValidator\ValueValidator;

class InputActionValidator implements ValidatorInterface
{
    const IDENTIFIER_KEYWORD = ' to ';

    public function handles(object $model): bool
    {
        return $model instanceof ActionInterface && ActionTypes::SET === $model->getType();
    }

    private $identifierValidator;
    private $valueValidator;

    public function __construct(IdentifierValidator $identifierValidator, ValueValidator $valueValidator)
    {
        $this->identifierValidator = $identifierValidator;
        $this->valueValidator = $valueValidator;
    }

    public static function create(): InputActionValidator
    {
        return new InputActionValidator(
            IdentifierValidator::create(),
            ValueValidator::create()
        );
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof InputActionInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $identifier = $model->getIdentifier();

        if (!$identifier instanceof IdentifierInterface) {
            return $this->createInvalidResult($model, ActionValidator::REASON_INPUT_ACTION_IDENTIFIER_MISSING);
        }

        if ($identifier instanceof AttributeIdentifierInterface) {
            return $this->createInvalidResult($model, ActionValidator::REASON_UNACTIONABLE_IDENTIFIER);
        }

        $identifierValidationResult = $this->identifierValidator->validate($identifier);

        if ($identifierValidationResult instanceof InvalidResultInterface) {
            return $this->createInvalidResult(
                $model,
                ActionValidator::REASON_INVALID_IDENTIFIER,
                $identifierValidationResult
            );
        }

        $value = $model->getValue();

        if (!$value instanceof ValueInterface) {
            return $this->createInvalidResult($model, ActionValidator::REASON_INPUT_ACTION_VALUE_MISSING);
        }

        if (!$value->isActionable()) {
            return $this->createInvalidResult($model, ActionValidator::REASON_INPUT_ACTION_UNACTIONABLE_VALUE);
        }

        $valueValidationResult = $this->valueValidator->validate($value);

        if ($valueValidationResult instanceof InvalidResultInterface) {
            return $this->createInvalidResult(
                $model,
                ActionValidator::REASON_INVALID_VALUE,
                $valueValidationResult
            );
        }

        if (!$this->hasToKeyword($model)) {
            return $this->createInvalidResult($model, ActionValidator::REASON_INPUT_ACTION_TO_KEYWORD_MISSING);
        }

        return new ValidResult($model);
    }

    private function hasToKeyword(InputActionInterface $action): bool
    {
        $arguments = $action->getArguments();

        if (mb_substr_count($arguments, self::IDENTIFIER_KEYWORD) === 0) {
            return false;
        }

        $argumentsWithoutSelector = mb_substr($arguments, mb_strlen((string) $action->getIdentifier()));

        $keyword = self::IDENTIFIER_KEYWORD;
        return mb_substr($argumentsWithoutSelector, 0, strlen($keyword)) === $keyword;
    }

    private function createInvalidResult(
        object $model,
        string $reason,
        ?InvalidResultInterface $previous = null
    ): ResultInterface {
        return new InvalidResult($model, TypeInterface::ACTION, $reason, $previous);
    }
}
