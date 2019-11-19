<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Identifier;

use webignition\BasilModel\Identifier\DomIdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValidatorInterface;

class DomIdentifierValidator implements ValidatorInterface
{
    public static function create(): DomIdentifierValidator
    {
        return new DomIdentifierValidator();
    }

    public function handles(object $model): bool
    {
        return $model instanceof DomIdentifierInterface;
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof DomIdentifierInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $locator = trim($model->getLocator());
        if ('' === $locator) {
            return $this->createInvalidResult($model, IdentifierValidator::REASON_ELEMENT_LOCATOR_MISSING);
        }

        if ('' === $model->getAttributeName()) {
            return $this->createInvalidResult($model, IdentifierValidator::REASON_ATTRIBUTE_NAME_EMPTY);
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
