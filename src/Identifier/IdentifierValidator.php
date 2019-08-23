<?php

namespace webignition\BasilModelValidator\Identifier;

use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\ValidatorInterface;

class IdentifierValidator implements ValidatorInterface
{
    const REASON_TYPE_INVALID = 'identifier-type-invalid';
    const REASON_VALUE_MISSING = 'identifier-value-missing';
    const REASON_INVALID_PARENT_IDENTIFIER = 'identifier-invalid-parent-identifier';
    const REASON_VALUE_TYPE_MISMATCH = 'identifier-value-type-mismatch';

    /**
     * @var ValidatorInterface[]
     */
    private $identifierTypeValidators = [];

    public function __construct()
    {
        $this->identifierTypeValidators[] = ElementIdentifierValidator::create();
    }

    public static function create(): IdentifierValidator
    {
        return new IdentifierValidator();
    }

    public function handles(object $model): bool
    {
        return $model instanceof IdentifierInterface;
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof IdentifierInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $typeSpecificIdentifierValidator = $this->findIdentifierTypeValidator($model);

        return null === $typeSpecificIdentifierValidator
            ? $this->createInvalidResult($model, self::REASON_TYPE_INVALID)
            : $typeSpecificIdentifierValidator->validate($model, $context);
    }

    private function findIdentifierTypeValidator(IdentifierInterface $identifier): ?ValidatorInterface
    {
        foreach ($this->identifierTypeValidators as $identifierTypeValidator) {
            if ($identifierTypeValidator->handles($identifier)) {
                return $identifierTypeValidator;
            }
        }

        return null;
    }

    private function createInvalidResult(
        object $model,
        string $reason,
        ?InvalidResultInterface $previous = null
    ): InvalidResultInterface {
        return new InvalidResult($model, TypeInterface::IDENTIFIER, $reason, $previous);
    }
}
