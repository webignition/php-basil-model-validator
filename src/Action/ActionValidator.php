<?php

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\ValidatorInterface;

class ActionValidator implements ValidatorInterface
{
    const CODE_INPUT_ACTION_IDENTIFIER_MISSING = 1;
    const CODE_INPUT_ACTION_VALUE_MISSING = 2;
    const CODE_INPUT_ACTION_TO_KEYWORD_MISSING = 3;
    const CODE_INTERACTION_ACTION_IDENTIFIER_MISSING = 4;
    const CODE_WAIT_ACTION_DURATION_MISSING = 5;

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
