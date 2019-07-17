<?php

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\ValidatorInterface;

class ActionValidator implements ValidatorInterface
{
    const REASON_INPUT_ACTION_IDENTIFIER_MISSING = 'action-input-action-identifier-missing';
    const REASON_INPUT_ACTION_VALUE_MISSING = 'input-action-value-missing';
    const REASON_INPUT_ACTION_TO_KEYWORD_MISSING = 'input-action-to-keyword-missing';
    const REASON_INTERACTION_ACTION_IDENTIFIER_MISSING = 'interaction-action-identifier-missing';
    const REASON_WAIT_ACTION_DURATION_MISSING = 'wait-action-duration-missing';
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
