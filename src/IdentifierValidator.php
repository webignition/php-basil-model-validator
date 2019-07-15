<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModelValidator\Result\InvalidIdentifierResult;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class IdentifierValidator implements ValidatorInterface
{
    const CODE_TYPE_INVALID = 1;
    const CODE_VALUE_MISSING = 2;
    const CODE_INVALID_PARENT_IDENTIFIER = 3;
    const CODE_INVALID_PAGE_OBJECT_PROPERTY = 4;
    const CODE_INVALID_BROWSER_OBJECT_PROPERTY = 5;

    const PAGE_OBJECT_PARAMETER_REGEX = '/^\$page\.+/';
    const BROWSER_OBJECT_PARAMETER_REGEX = '/^\$browser\.+/';

    const VALID_TYPES = [
        IdentifierTypes::CSS_SELECTOR,
        IdentifierTypes::XPATH_EXPRESSION,
        IdentifierTypes::PAGE_MODEL_ELEMENT_REFERENCE,
        IdentifierTypes::ELEMENT_PARAMETER,
        IdentifierTypes::PAGE_OBJECT_PARAMETER,
        IdentifierTypes::BROWSER_OBJECT_PARAMETER,
    ];

    const VALID_PAGE_OBJECT_PROPERTY_NAMES = [
        'url',
        'title',
    ];

    const VALID_BROWSER_OBJECT_PROPERTY_NAMES = [
        'size',
    ];

    public function handles(object $model): bool
    {
        return $model instanceof IdentifierInterface;
    }

    public function validate(object $model): ResultInterface
    {
        if (!$model instanceof IdentifierInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        if (!in_array($model->getType(), self::VALID_TYPES)) {
            return $this->createInvalidResult($model, self::CODE_TYPE_INVALID);
        }

        if ($model->getValue()->isEmpty()) {
            return $this->createInvalidResult($model, self::CODE_VALUE_MISSING);
        }

        $parentIdentifier = $model->getParentIdentifier();

        if ($parentIdentifier instanceof IdentifierInterface) {
            $parentValidationResult = $this->validate($parentIdentifier);

            if (false === $parentValidationResult->getIsValid()) {
                return $this->createInvalidResult($model, self::CODE_INVALID_PARENT_IDENTIFIER);
            }
        }

        if (IdentifierTypes::PAGE_OBJECT_PARAMETER === $model->getType()) {
            $propertyName = $this->getObjectPropertyName(self::PAGE_OBJECT_PARAMETER_REGEX, $model->getValue());

            if (!in_array($propertyName, self::VALID_PAGE_OBJECT_PROPERTY_NAMES)) {
                $result = $this->createInvalidResult($model, self::CODE_INVALID_PAGE_OBJECT_PROPERTY);
                $result->setPageProperty($propertyName);

                return $result;
            }
        }

        if (IdentifierTypes::BROWSER_OBJECT_PARAMETER === $model->getType()) {
            $propertyName = $this->getObjectPropertyName(self::BROWSER_OBJECT_PARAMETER_REGEX, $model->getValue());

            if (!in_array($propertyName, self::VALID_BROWSER_OBJECT_PROPERTY_NAMES)) {
                $result = $this->createInvalidResult($model, self::CODE_INVALID_BROWSER_OBJECT_PROPERTY);
                $result->setBrowserProperty($propertyName);

                return $result;
            }
        }

        return new ValidResult($model);
    }

    private function createInvalidResult(object $model, int $code): InvalidIdentifierResult
    {
        return new InvalidIdentifierResult($model, $code);
    }

    private function getObjectPropertyName(string $pattern, string $subject): string
    {
        return (string) preg_replace($pattern, '', $subject);
    }
}
