<?php

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\InteractionActionInterface;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModelValidator\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValidatorInterface;

class InteractionActionValidator implements ValidatorInterface
{
    private $identifierValidator;

    public function __construct(IdentifierValidator $identifierValidator)
    {
        $this->identifierValidator = $identifierValidator;
    }

    public static function create(): InteractionActionValidator
    {
        return new InteractionActionValidator(
            IdentifierValidator::create()
        );
    }

    public function handles(object $model): bool
    {
        return $model instanceof ActionInterface &&
            in_array($model->getType(), [ActionTypes::CLICK, ActionTypes::SUBMIT, ActionTypes::WAIT_FOR]);
    }

    public function validate(object $model): ResultInterface
    {
        if (!$model instanceof InteractionActionInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $identifier = $model->getIdentifier();

        if (!$identifier instanceof IdentifierInterface) {
            return $this->createInvalidResult($model, ActionValidator::CODE_INTERACTION_ACTION_IDENTIFIER_MISSING);
        }

        $identifierValidationResult = $this->identifierValidator->validate($identifier);

        if ($identifierValidationResult instanceof InvalidResultInterface) {
            return $this->createInvalidResult(
                $model,
                ActionValidator::CODE_INVALID_IDENTIFIER,
                $identifierValidationResult
            );
        }

        if (false === $identifier->isActionable()) {
            return $this->createInvalidResult($model, ActionValidator::CODE_UNACTIONABLE_IDENTIFIER);
        }

        return new ValidResult($model);
    }

    private function createInvalidResult(
        object $model,
        int $code,
        ?InvalidResultInterface $previous = null
    ): ResultInterface {
        return new InvalidResult($model, TypeInterface::ACTION, $code, $previous);
    }
}
