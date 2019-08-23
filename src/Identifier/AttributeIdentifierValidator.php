<?php

namespace webignition\BasilModelValidator\Identifier;

use webignition\BasilModel\Identifier\AttributeIdentifierInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValidatorInterface;

class AttributeIdentifierValidator implements ValidatorInterface
{
    const REASON_INVALID_ELEMENT_IDENTIFIER = 'attribute-identifier-invalid-element-identifier';
    const REASON_ATTRIBUTE_NAME_MISSING = 'attribute-identifier-attribute-name-missing';

    private $elementIdentifierValidator;

    public function __construct(ElementIdentifierValidator $elementIdentifierValidator)
    {
        $this->elementIdentifierValidator = $elementIdentifierValidator;
    }

    public static function create(): AttributeIdentifierValidator
    {
        return new AttributeIdentifierValidator(
            ElementIdentifierValidator::create()
        );
    }

    public function handles(object $model): bool
    {
        return $model instanceof AttributeIdentifierInterface;
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof AttributeIdentifierInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $elementIdentifier = $model->getElementIdentifier();
        $elementIdentifierValidationResult = $this->elementIdentifierValidator->validate($elementIdentifier);

        if ($elementIdentifierValidationResult instanceof InvalidResultInterface) {
            return $this->createInvalidResult(
                $model,
                self::REASON_INVALID_ELEMENT_IDENTIFIER,
                $elementIdentifierValidationResult
            );
        }

        $attributeName = trim((string) $model->getAttributeName());
        if ('' === $attributeName) {
            return $this->createInvalidResult(
                $model,
                self::REASON_ATTRIBUTE_NAME_MISSING
            );
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
