<?php

namespace webignition\BasilModelValidator\Action;

class Factory
{
    public static function create(): ActionValidator
    {
        $actionValidator = new ActionValidator();

        $actionValidator->addActionTypeValidator(new InputActionValidator());
        $actionValidator->addActionTypeValidator(new InteractionActionValidator());
        $actionValidator->addActionTypeValidator(new NoArgumentsActionValidator());
        $actionValidator->addActionTypeValidator(new WaitActionValidator());

        return $actionValidator;
    }
}
