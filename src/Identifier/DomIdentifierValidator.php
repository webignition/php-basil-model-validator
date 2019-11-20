<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Identifier;

use webignition\BasilModel\Identifier\DomIdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModelValidator\TypeInterface;
use webignition\BasilValidationResult\InvalidResult;
use webignition\BasilValidationResult\InvalidResultInterface;
use webignition\BasilValidationResult\ResultInterface;
use webignition\BasilValidationResult\ValidResult;

class DomIdentifierValidator
{
    public static function create(): DomIdentifierValidator
    {
        return new DomIdentifierValidator();
    }

    public function validate(DomIdentifierInterface $identifier): ResultInterface
    {
        $locator = trim($identifier->getLocator());
        if ('' === $locator) {
            return $this->createInvalidResult($identifier, IdentifierValidator::REASON_ELEMENT_LOCATOR_MISSING);
        }

        if ('' === $identifier->getAttributeName()) {
            return $this->createInvalidResult($identifier, IdentifierValidator::REASON_ATTRIBUTE_NAME_EMPTY);
        }

        $parentIdentifier = $identifier->getParentIdentifier();

        if ($parentIdentifier instanceof IdentifierInterface) {
            $parentValidationResult = $this->validate($parentIdentifier);

            if ($parentValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult(
                    $identifier,
                    IdentifierValidator::REASON_INVALID_PARENT_IDENTIFIER,
                    $parentValidationResult
                );
            }
        }

        return new ValidResult($identifier);
    }

    private function createInvalidResult(
        DomIdentifierInterface $identifier,
        string $reason,
        ?InvalidResultInterface $previous = null
    ): InvalidResultInterface {
        return new InvalidResult($identifier, TypeInterface::IDENTIFIER, $reason, $previous);
    }
}
