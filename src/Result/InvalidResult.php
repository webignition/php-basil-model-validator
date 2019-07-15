<?php

namespace webignition\BasilModelValidator\Result;

class InvalidResult extends AbstractResult
{
    public function __construct(object $model, int $type, int $code = 0)
    {
        parent::__construct(false, $model, $type, $code);
    }

    public static function createUnhandledModelResult(object $model): ResultInterface
    {
        return new InvalidResult($model, TypeInterface::UNHANDLED);
    }
}
