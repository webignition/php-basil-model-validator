<?php

namespace webignition\BasilModelValidator\Result;

interface InvalidResultInterface extends ResultInterface
{
    public function getType(): string;
    public function getReason(): string;
    public function getPrevious(): InvalidResultInterface;
}
