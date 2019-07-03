<?php

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\WaitActionInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValidatorInterface;

class WaitActionValidator implements ValidatorInterface
{
    public function handles(object $model): bool
    {
        return $model instanceof ActionInterface && ActionTypes::WAIT === $model->getType();
    }

    public function validate(object $model): ResultInterface
    {
        if (!$model instanceof WaitActionInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        if (empty($model->getDuration())) {
            return new InvalidResult(
                $model,
                TypeInterface::ACTION,
                InvalidResultCode::CODE_WAIT_ACTION_DURATION_MISSING
            );
        }

        return new ValidResult($model);
    }
}