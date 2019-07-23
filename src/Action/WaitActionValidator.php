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
    public static function create(): WaitActionValidator
    {
        return new WaitActionValidator();
    }

    public function handles(object $model): bool
    {
        return $model instanceof ActionInterface && ActionTypes::WAIT === $model->getType();
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof WaitActionInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $duration = $model->getDuration();

        if ($duration->isEmpty()) {
            return new InvalidResult(
                $model,
                TypeInterface::ACTION,
                ActionValidator::REASON_WAIT_ACTION_DURATION_MISSING
            );
        }

        if (!$duration->isActionable()) {
            return new InvalidResult(
                $model,
                TypeInterface::ACTION,
                ActionValidator::REASON_WAIT_ACTION_DURATION_UNACTIONABLE
            );
        }

        return new ValidResult($model);
    }
}
