<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Result;

abstract class AbstractResult implements ResultInterface
{
    private $isValid;
    private $model;

    public function __construct(bool $isValid, object $model)
    {
        $this->isValid = $isValid;
        $this->model = $model;
    }

    public function getIsValid(): bool
    {
        return $this->isValid;
    }

    public function getModel(): object
    {
        return $this->model;
    }
}
