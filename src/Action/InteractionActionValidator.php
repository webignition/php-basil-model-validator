<?php

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\InteractionActionInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValidatorInterface;

class InteractionActionValidator implements ValidatorInterface
{
    public function handles(object $model): bool
    {
        return $model instanceof ActionInterface &&
            in_array($model->getType(), [ActionTypes::CLICK, ActionTypes::SUBMIT, ActionTypes::WAIT_FOR]);
    }

    public function validate(object $model): ResultInterface
    {
        if (!$model instanceof InteractionActionInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        if (null === $model->getIdentifier()) {
            return new InvalidResult(
                $model,
                TypeInterface::ACTION,
                InvalidResultCode::CODE_INTERACTION_ACTION_IDENTIFIER_MISSING
            );
        }

        return new ValidResult($model);
    }
}
