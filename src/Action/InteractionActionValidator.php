<?php

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\InteractionActionInterface;
use webignition\BasilModel\Identifier\DomIdentifierInterface;
use webignition\BasilModelValidator\Identifier\IdentifierValidator;
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

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof InteractionActionInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $identifier = $model->getIdentifier();

        if ($identifier instanceof DomIdentifierInterface && null !== $identifier->getAttributeName()) {
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

        return new ValidResult($model);
    }

    private function createInvalidResult(
        object $model,
        string $reason,
        ?InvalidResultInterface $previous = null
    ): ResultInterface {
        return new InvalidResult($model, TypeInterface::ACTION, $reason, $previous);
    }
}
