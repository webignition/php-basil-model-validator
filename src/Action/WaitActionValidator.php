<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\WaitActionInterface;
use webignition\BasilModelValidator\TypeInterface;
use webignition\BasilValidationResult\InvalidResult;
use webignition\BasilValidationResult\ResultInterface;
use webignition\BasilValidationResult\ValidResult;

class WaitActionValidator
{
    public static function create(): WaitActionValidator
    {
        return new WaitActionValidator();
    }

    public function validate(WaitActionInterface $action): ResultInterface
    {
        $duration = $action->getDuration();

        if ($duration->isEmpty()) {
            return new InvalidResult(
                $action,
                TypeInterface::ACTION,
                ActionValidator::REASON_WAIT_ACTION_DURATION_MISSING
            );
        }

        if (!$duration->isActionable()) {
            return new InvalidResult(
                $action,
                TypeInterface::ACTION,
                ActionValidator::REASON_WAIT_ACTION_DURATION_UNACTIONABLE
            );
        }

        return new ValidResult($action);
    }
}
