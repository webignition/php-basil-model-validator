<?php

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValidatorInterface;

class NoArgumentsActionValidator implements ValidatorInterface
{
    public static function create(): NoArgumentsActionValidator
    {
        return new NoArgumentsActionValidator();
    }

    public function handles(object $model): bool
    {
        return $model instanceof ActionInterface &&
            in_array($model->getType(), [ActionTypes::RELOAD, ActionTypes::BACK, ActionTypes::FORWARD]);
    }

    public function validate(object $model): ResultInterface
    {
        return $this->handles($model)
            ? new ValidResult($model)
            : InvalidResult::createUnhandledModelResult($model);
    }
}
