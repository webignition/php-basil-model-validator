<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\InputActionInterface;
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
    private const IDENTIFIER_KEYWORD = ' to ';

    private $identifierValidator;
    private $valueValidator;
    private $interactionActionValidator;

    public function __construct(
        IdentifierValidator $identifierValidator,
        ValueValidator $valueValidator,
        InteractionActionValidator $interactionActionValidator
    ) {
        $this->identifierValidator = $identifierValidator;
        $this->valueValidator = $valueValidator;
        $this->interactionActionValidator = $interactionActionValidator;
    }

    public function handles(object $model): bool
    {
        return $model instanceof ActionInterface && ActionTypes::SET === $model->getType();
    }

    public static function create(): InputActionValidator
    {
        return new InputActionValidator(
            IdentifierValidator::create(),
            ValueValidator::create(),
            InteractionActionValidator::create()
        );
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof InputActionInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $interactionActionValidatorResult = $this->interactionActionValidator->validate($model, $context);
        if ($interactionActionValidatorResult instanceof InvalidResultInterface) {
            return $interactionActionValidatorResult;
        }

        $value = $model->getValue();

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
