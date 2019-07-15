<?php

namespace webignition\BasilModelValidator\Result;

interface ResultInterface
{
    public function getIsValid(): bool;
    public function getModel(): object;
    public function getType(): int;
    public function getCode(): int;
}
