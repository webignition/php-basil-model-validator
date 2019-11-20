<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\InteractionActionInterface;
use webignition\BasilModel\Identifier\DomIdentifierInterface;
use webignition\BasilModelValidator\Identifier\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class InteractionActionValidator
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

    public function validate(InteractionActionInterface $action): ResultInterface
    {
        $identifier = $action->getIdentifier();

        if ($identifier instanceof DomIdentifierInterface && null !== $identifier->getAttributeName()) {
            return $this->createInvalidResult($action, ActionValidator::REASON_UNACTIONABLE_IDENTIFIER);
        }

        $identifierValidationResult = $this->identifierValidator->validate($identifier);

        if ($identifierValidationResult instanceof InvalidResultInterface) {
            return $this->createInvalidResult(
                $action,
                ActionValidator::REASON_INVALID_IDENTIFIER,
                $identifierValidationResult
            );
        }

        return new ValidResult($action);
    }

    private function createInvalidResult(
        InteractionActionInterface $action,
        string $reason,
        ?InvalidResultInterface $previous = null
    ): ResultInterface {
        return new InvalidResult($action, TypeInterface::ACTION, $reason, $previous);
    }
}
