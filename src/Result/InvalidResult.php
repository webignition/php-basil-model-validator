<?php

namespace webignition\BasilModelValidator\Result;

class InvalidResult extends AbstractResult implements InvalidResultInterface
{
    private $type;
    private $code;

    public function __construct(object $model, int $type, int $code)
    {
        parent::__construct(false, $model);

        $this->type = $type;
        $this->code = $code;
    }

    public static function createUnhandledModelResult(object $model): InvalidResultInterface
    {
        return new InvalidResult($model, TypeInterface::UNHANDLED, 0);
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getCode(): int
    {
        return $this->code;
    }
}
