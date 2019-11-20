<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator;

use webignition\BasilModel\DataSet\DataSetInterface;
use webignition\BasilValidationResult\InvalidResult;
use webignition\BasilValidationResult\ResultInterface;
use webignition\BasilValidationResult\ValidResult;

class DataSetValidator
{
    public const REASON_DATA_SET_INCOMPLETE = 'data-set-incomplete';

    public static function create(): DataSetValidator
    {
        return new DataSetValidator();
    }

    public function validate(DataSetInterface $dataSet, ?string $dataParameterName): ResultInterface
    {
        if (is_string($dataParameterName)) {
            if (false === $dataSet->hasParameterNames([$dataParameterName])) {
                return new InvalidResult(
                    $dataSet,
                    TypeInterface::DATA_SET,
                    self::REASON_DATA_SET_INCOMPLETE
                );
            }
        }

        return new ValidResult($dataSet);
    }
}
