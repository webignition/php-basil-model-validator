<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\InputActionInterface;
use webignition\BasilModelValidator\Identifier\IdentifierValidator;
use webignition\BasilModelValidator\TypeInterface;
use webignition\BasilModelValidator\ValueValidator;
use webignition\BasilValidationResult\InvalidResult;
use webignition\BasilValidationResult\InvalidResultInterface;
use webignition\BasilValidationResult\ResultInterface;
use webignition\BasilValidationResult\ValidResult;

class InputActionValidator
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

    public static function create(): InputActionValidator
    {
        return new InputActionValidator(
            IdentifierValidator::create(),
            ValueValidator::create(),
            InteractionActionValidator::create()
        );
    }

    public function validate(InputActionInterface $action): ResultInterface
    {
        $interactionActionValidatorResult = $this->interactionActionValidator->validate($action);
        if ($interactionActionValidatorResult instanceof InvalidResultInterface) {
            return $interactionActionValidatorResult;
        }

        $value = $action->getValue();

        if (!$value->isActionable()) {
            return $this->createInvalidResult($action, ActionValidator::REASON_INPUT_ACTION_UNACTIONABLE_VALUE);
        }

        $valueValidationResult = $this->valueValidator->validate($value);

        if ($valueValidationResult instanceof InvalidResultInterface) {
            return $this->createInvalidResult(
                $action,
                ActionValidator::REASON_INVALID_VALUE,
                $valueValidationResult
            );
        }

        if (!$this->hasToKeyword($action)) {
            return $this->createInvalidResult($action, ActionValidator::REASON_INPUT_ACTION_TO_KEYWORD_MISSING);
        }

        return new ValidResult($action);
    }

    private function hasToKeyword(InputActionInterface $action): bool
    {
        $arguments = $action->getArguments();

        $argumentsWithoutSelector = mb_substr($arguments, mb_strlen((string) $action->getIdentifier()));

        $keyword = self::IDENTIFIER_KEYWORD;
        return mb_substr($argumentsWithoutSelector, 0, strlen($keyword)) === $keyword;
    }

    private function createInvalidResult(
        InputActionInterface $action,
        string $reason,
        ?InvalidResultInterface $previous = null
    ): ResultInterface {
        return new InvalidResult($action, TypeInterface::ACTION, $reason, $previous);
    }
}
