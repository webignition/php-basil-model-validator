<?php

namespace webignition\BasilModelValidator\Result;

abstract class AbstractResult implements ResultInterface
{
    private $isValid;
    private $model;
    private $type;
    private $code;
    private $message;

    public function __construct(
        bool $isValid,
        object $model,
        int $type = TypeInterface::NOT_APPLICABLE,
        int $code = 0,
        string $message = ''
    ) {
        $this->isValid = $isValid;
        $this->model = $model;
        $this->type = $type;
        $this->code = $code;
        $this->message = $message;
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

    public function getMessage(): string
    {
        return $this->message;
    }
}
