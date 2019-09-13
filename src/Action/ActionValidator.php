<?php

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\ValidatorInterface;

class ActionValidator implements ValidatorInterface
{
    const REASON_INPUT_ACTION_TO_KEYWORD_MISSING = 'input-action-to-keyword-missing';
    const REASON_INPUT_ACTION_UNACTIONABLE_VALUE = 'input-action-unactionable-value';
    const REASON_WAIT_ACTION_DURATION_MISSING = 'wait-action-duration-missing';
    const REASON_WAIT_ACTION_DURATION_UNACTIONABLE = 'wait-action-duration-unactionable';
    const REASON_UNACTIONABLE_IDENTIFIER = 'action-unactionable-identifier';
    const REASON_INVALID_IDENTIFIER = 'action-invalid-identifier';
    const REASON_INVALID_VALUE = 'action-invalid-value';

    /**
     * @var ValidatorInterface[]
     */
    private $actionTypeValidators = [];

    public function __construct()
    {
        $this->actionTypeValidators[] = InputActionValidator::create();
        $this->actionTypeValidators[] = InteractionActionValidator::create();
        $this->actionTypeValidators[] = NoArgumentsActionValidator::create();
        $this->actionTypeValidators[] = WaitActionValidator::create();
    }

    public static function create(): ActionValidator
    {
        return new ActionValidator();
    }

    public function handles(object $model): bool
    {
        return $model instanceof ActionInterface;
    }

    public function validate(object $model, ?array $context = []): ResultInterface
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
