<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModelValidator\Result\ResultInterface;

interface ValidatorInterface
{
    public function handles(object $model): bool;
    public function validate(object $model, ?array $context = []): ResultInterface;
}
