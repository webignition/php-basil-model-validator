<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Result;

interface ResultInterface
{
    public function getIsValid(): bool;
    public function getSubject();
}
