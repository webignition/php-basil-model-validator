<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Action\InputActionInterface;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Step\StepInterface;
use webignition\BasilModelValidator\Action\ActionValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class StepValidator implements ValidatorInterface
{
    const CODE_ACTION_INVALID = 1;
    const CODE_ASSERTION_INVALID = 2;

    private $actionValidator;
    private $assertionValidator;

    public function __construct(ActionValidator $actionValidator, AssertionValidator $assertionValidator)
    {
        $this->actionValidator = $actionValidator;
        $this->assertionValidator = $assertionValidator;
    }

    public static function create(): StepValidator
    {
        return new StepValidator(
            ActionValidator::create(),
            AssertionValidator::create()
        );
    }

    public function handles(object $model): bool
    {
        return $model instanceof StepInterface;
    }

    public function validate(object $model): ResultInterface
    {
        if (!$model instanceof StepInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        // actions valid
        // assertions valid
        // actions with data parameters have data sets
        // assertions with data parameters have data sets
        // actions with element parameters have element identifiers
        // assertions with element parameters have element identifiers
        // actions have actionable identifiers
        // assertions have assertable identifiers (?)


        // actions requiring actionable identifiers
        // relevant action verbs:
        // click
        // set
        // submit
        // wait-for

        foreach ($model->getActions() as $action) {
            $actionValidationResult = $this->actionValidator->validate($action);

            if ($actionValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult($model, self::CODE_ACTION_INVALID, $actionValidationResult);
            }

//            if ($action instanceof InputActionInterface) {
//                $identifier = $action->getIdentifier();
//
//                if ($identifier instanceof IdentifierInterface) {
//                    $identifier->getType();
//                }
//
//                $action->getValue();
//                $action->getIdentifier();
//            }
        }

        foreach ($model->getAssertions() as $assertion) {
            $assertionValidationResult = $this->assertionValidator->validate($assertion);

            if ($assertionValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult($model, self::CODE_ASSERTION_INVALID, $assertionValidationResult);
            }
        }

        return new ValidResult($model);
    }

    private function createInvalidResult(
        object $model,
        int $code,
        ?InvalidResultInterface $previous = null
    ): InvalidResultInterface {
        return new InvalidResult($model, TypeInterface::STEP, $code, $previous);
    }
}
