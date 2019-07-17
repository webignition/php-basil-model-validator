<?php

namespace webignition\BasilModelValidator\Result;

class InvalidResult extends AbstractResult implements InvalidResultInterface
{
    private $type;
    private $reason;
    private $previous;

    public function __construct(object $model, string $type, string $reason, ?InvalidResultInterface $previous = null)
    {
        parent::__construct(false, $model);

        $this->type = $type;
        $this->reason = $reason;
        $this->previous = $previous;
    }

    public static function createUnhandledModelResult(object $model): InvalidResultInterface
    {
        return new InvalidResult($model, TypeInterface::UNHANDLED, '');
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getPrevious(): InvalidResultInterface
    {
        return $this->previous;
    }
}
