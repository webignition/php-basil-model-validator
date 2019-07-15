<?php

namespace webignition\BasilModelValidator\Result;

abstract class AbstractResult implements ResultInterface
{
    private $isValid;
    private $model;
    private $type;
    private $code;

    public function __construct(
        bool $isValid,
        object $model,
        int $type = TypeInterface::NOT_APPLICABLE,
        int $code = 0
    ) {
        $this->isValid = $isValid;
        $this->model = $model;
        $this->type = $type;
        $this->code = $code;
    }

    public function getIsValid(): bool
    {
        return $this->isValid;
    }

    public function getModel(): object
    {
        return $this->model;
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
