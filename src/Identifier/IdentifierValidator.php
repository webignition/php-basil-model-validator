<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Identifier;

use webignition\BasilModel\Identifier\DomIdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;

class IdentifierValidator
{
    public const REASON_TYPE_INVALID = 'identifier-type-invalid';
    public const REASON_ELEMENT_LOCATOR_MISSING = 'identifier-element-locator-missing';
    public const REASON_INVALID_PARENT_IDENTIFIER = 'identifier-invalid-parent-identifier';
    public const REASON_ATTRIBUTE_NAME_EMPTY = 'identifier-attribute-name-empty';

    private $domIdentifierValidator;

    public function __construct()
    {
        $this->domIdentifierValidator = DomIdentifierValidator::create();
    }

    public static function create(): IdentifierValidator
    {
        return new IdentifierValidator();
    }

    public function validate(IdentifierInterface $identifier): ResultInterface
    {
        if ($identifier instanceof DomIdentifierInterface) {
            return $this->domIdentifierValidator->validate($identifier);
        }

        return new InvalidResult($identifier, TypeInterface::IDENTIFIER, self::REASON_TYPE_INVALID);
    }
}
