<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator;

use webignition\BasilModel\DataSet\DataSetInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class DataSetValidator implements ValidatorInterface
{
    public const CONTEXT_DATA_PARAMETER_NAME = 'data-parameter-name';
    public const REASON_DATA_SET_INCOMPLETE = 'data-set-incomplete';

    public static function create(): DataSetValidator
    {
        return new DataSetValidator();
    }

    public function handles(object $model): bool
    {
        return $model instanceof DataSetInterface;
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof DataSetInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $dataParameterName = $context[self::CONTEXT_DATA_PARAMETER_NAME] ?? null;

        if (is_string($dataParameterName)) {
            if (false === $model->hasParameterNames([$dataParameterName])) {
                return new InvalidResult(
                    $model,
                    TypeInterface::DATA_SET,
                    self::REASON_DATA_SET_INCOMPLETE
                );
            }
        }

        return new ValidResult($model);
    }
}
