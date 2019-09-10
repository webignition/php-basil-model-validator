<?php

namespace webignition\BasilModelValidator\Identifier;

use webignition\BasilModel\Identifier\ElementIdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValidatorInterface;

class ElementIdentifierValidator implements ValidatorInterface
{
    public static function create(): ElementIdentifierValidator
    {
        return new ElementIdentifierValidator();
    }

    public function handles(object $model): bool
    {
        return $model instanceof ElementIdentifierInterface;
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof ElementIdentifierInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $elementExpression = trim($model->getElementExpression()->getExpression());
        if ('' === $elementExpression) {
            return $this->createInvalidResult($model, IdentifierValidator::REASON_ELEMENT_EXPRESSION_MISSING);
        }

        $parentIdentifier = $model->getParentIdentifier();

        if ($parentIdentifier instanceof IdentifierInterface) {
            $parentValidationResult = $this->validate($parentIdentifier);

            if ($parentValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult(
                    $model,
                    IdentifierValidator::REASON_INVALID_PARENT_IDENTIFIER,
                    $parentValidationResult
                );
            }
        }

        return new ValidResult($model);
    }

    private function createInvalidResult(
        object $model,
        string $reason,
        ?InvalidResultInterface $previous = null
    ): InvalidResultInterface {
        return new InvalidResult($model, TypeInterface::IDENTIFIER, $reason, $previous);
    }
}
