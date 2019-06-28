<?php

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\ValidatorInterface;

class ActionValidator implements ValidatorInterface
{
    /**
     * @var ValidatorInterface[]
     */
    private $actionTypeValidators = [];

    public function addActionTypeValidator(ValidatorInterface $actionTypeValidator)
    {
        $this->actionTypeValidators[] = $actionTypeValidator;
    }

    public function handles(object $model): bool
    {
        return $model instanceof ActionInterface;
    }

    public function validate(object $model): ResultInterface
    {
        if (!$model instanceof ActionInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $typeSpecificActionValidator = $this->findTypeSpecificActionValidator($model);

        return null === $typeSpecificActionValidator
            ? InvalidResult::createUnhandledModelResult($model)
            : $typeSpecificActionValidator->validate($model);
    }

    private function findTypeSpecificActionValidator(ActionInterface $action): ?ValidatorInterface
    {
        foreach ($this->actionTypeValidators as $typeSpecificActionValidator) {
            if ($typeSpecificActionValidator->handles($action)) {
                return $typeSpecificActionValidator;
            }
        }

        return null;
    }
}
