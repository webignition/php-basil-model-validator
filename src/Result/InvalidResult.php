<?php

namespace webignition\BasilModelValidator\Result;

class InvalidResult extends AbstractResult implements InvalidResultInterface
{
    private $type;
    private $code;
    private $previous;

    public function __construct(object $model, int $type, int $code, ?InvalidResultInterface $previous = null)
    {
        parent::__construct(false, $model);

        $this->type = $type;
        $this->code = $code;
        $this->previous = $previous;
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

    public function getPrevious(): InvalidResultInterface
    {
        return $this->previous;
    }
}
