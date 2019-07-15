<?php

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\InputActionInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\ValidatorInterface;

class InputActionValidator implements ValidatorInterface
{
    const IDENTIFIER_KEYWORD = ' to ';

    public function handles(object $model): bool
    {
        return $model instanceof ActionInterface && ActionTypes::SET === $model->getType();
    }

    public static function create(): InputActionValidator
    {
        return new InputActionValidator();
    }

    public function validate(object $model): ResultInterface
    {
        if (!$model instanceof InputActionInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        $identifier = $model->getIdentifier();

        if (null === $identifier) {
            return $this->createInvalidResult($model, ActionValidator::CODE_INPUT_ACTION_IDENTIFIER_MISSING);
        }

        if (false === $identifier->isActionable()) {
            return $this->createInvalidResult($model, ActionValidator::CODE_INPUT_ACTION_UNACTIONABLE_IDENTIFIER);
        }

        if (null === $model->getValue()) {
            return $this->createInvalidResult($model, ActionValidator::CODE_INPUT_ACTION_VALUE_MISSING);
        }

        if (!$this->hasToKeyword($model)) {
            return $this->createInvalidResult($model, ActionValidator::CODE_INPUT_ACTION_TO_KEYWORD_MISSING);
        }

        return new ValidResult($model);
    }

    private function hasToKeyword(InputActionInterface $action): bool
    {
        $arguments = $action->getArguments();

        if (mb_substr_count($arguments, self::IDENTIFIER_KEYWORD) === 0) {
            return false;
        }

        $argumentsWithoutSelector = mb_substr($arguments, mb_strlen((string) $action->getIdentifier()));

        $keyword = self::IDENTIFIER_KEYWORD;
        return mb_substr($argumentsWithoutSelector, 0, strlen($keyword)) === $keyword;
    }

    private function createInvalidResult(object $model, int $code): ResultInterface
    {
        return new InvalidResult($model, TypeInterface::ACTION, $code);
    }
}
