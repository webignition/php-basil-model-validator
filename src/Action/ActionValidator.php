<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\InputActionInterface;
use webignition\BasilModel\Action\WaitActionInterface;
use webignition\BasilValidationResult\InvalidResult;
use webignition\BasilValidationResult\ResultInterface;
use webignition\BasilValidationResult\ValidResult;

class ActionValidator
{
    public const REASON_INPUT_ACTION_TO_KEYWORD_MISSING = 'input-action-to-keyword-missing';
    public const REASON_INPUT_ACTION_UNACTIONABLE_VALUE = 'input-action-unactionable-value';
    public const REASON_WAIT_ACTION_DURATION_MISSING = 'wait-action-duration-missing';
    public const REASON_WAIT_ACTION_DURATION_UNACTIONABLE = 'wait-action-duration-unactionable';
    public const REASON_UNACTIONABLE_IDENTIFIER = 'action-unactionable-identifier';
    public const REASON_INVALID_IDENTIFIER = 'action-invalid-identifier';
    public const REASON_INVALID_VALUE = 'action-invalid-value';

    private $inputActionValidator;
    private $interactionActionValidator;
    private $waitActionValidator;

    public function __construct()
    {
        $this->inputActionValidator = InputActionValidator::create();
        $this->interactionActionValidator = InteractionActionValidator::create();
        $this->waitActionValidator = WaitActionValidator::create();
    }

    public static function create(): ActionValidator
    {
        return new ActionValidator();
    }

    public function validate(ActionInterface $action): ResultInterface
    {
        if ($action instanceof InputActionInterface && ActionTypes::SET === $action->getType()) {
            return $this->inputActionValidator->validate($action);
        }

        if (in_array($action->getType(), [ActionTypes::CLICK, ActionTypes::SUBMIT, ActionTypes::WAIT_FOR])) {
            return $this->interactionActionValidator->validate($action);
        }

        if (in_array($action->getType(), [ActionTypes::RELOAD, ActionTypes::BACK, ActionTypes::FORWARD])) {
            return new ValidResult($action);
        }

        if ($action instanceof WaitActionInterface && ActionTypes::WAIT === $action->getType()) {
            return $this->waitActionValidator->validate($action);
        }

        return InvalidResult::createUnhandledSubjectResult($action);
    }
}
