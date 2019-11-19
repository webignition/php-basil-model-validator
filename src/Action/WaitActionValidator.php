<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Action;

use webignition\BasilModel\Action\WaitActionInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

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
