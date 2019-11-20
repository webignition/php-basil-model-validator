<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Value\ObjectValueInterface;
use webignition\BasilModel\Value\ObjectValueType;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class ValueValidator
{
    public const REASON_PROPERTY_NAME_INVALID = 'value-property-name-invalid';
    public const REASON_UNACTIONABLE = 'value-unactionable';

    private const OBJECT_PROPERTY_WHITELIST = [
        ObjectValueType::BROWSER_PROPERTY => [
            'size',
        ],
        ObjectValueType::PAGE_PROPERTY => [
            'title',
            'url',
        ],
    ];

    public static function create(): ValueValidator
    {
        return new ValueValidator();
    }

    public function validate(ValueInterface $value): ResultInterface
    {
        if (!$value->isActionable()) {
            return new InvalidResult($value, TypeInterface::VALUE, self::REASON_UNACTIONABLE);
        }

        if ($value instanceof ObjectValueInterface) {
            $propertyWhitelist = self::OBJECT_PROPERTY_WHITELIST[$value->getType()] ?? null;

            if (is_array($propertyWhitelist) && !in_array($value->getProperty(), $propertyWhitelist)) {
                return new InvalidResult($value, TypeInterface::VALUE, self::REASON_PROPERTY_NAME_INVALID);
            }
        }

        return new ValidResult($value);
    }
}
