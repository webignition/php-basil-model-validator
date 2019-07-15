<?php

namespace webignition\BasilModelValidator\Result;

interface InvalidResultInterface extends ResultInterface
{
    public function getType(): int;
    public function getCode(): int;
    public function getPrevious(): InvalidResultInterface;
}
