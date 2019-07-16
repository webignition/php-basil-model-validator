<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\DataSet\DataSetCollectionInterface;
use webignition\BasilModel\DataSet\DataSetInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class DataSetCollectionValidator implements ValidatorInterface
{
    const CODE_KEY_MISMATCH = 1;

    public static function create(): DataSetCollectionValidator
    {
        return new DataSetCollectionValidator();
    }

    public function handles(object $model): bool
    {
        return $model instanceof DataSetCollectionInterface;
    }

    public function validate(object $model): ResultInterface
    {
        if (!$model instanceof DataSetCollectionInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        if (count($model) > 1) {
            /* @var DataSetInterface $firstDataSet */
            $firstDataSet = $model[0];
            $keys = $firstDataSet->getParameterNames();

            /* @var DataSetInterface $dataSet */
            foreach ($model as $dataSet) {
                if ($dataSet->getParameterNames() !== $keys) {
                    return new InvalidResult(
                        $model,
                        TypeInterface::DATA_SET,
                        self::CODE_KEY_MISMATCH
                    );
                }
            }
        }

        return new ValidResult($model);
    }
}
