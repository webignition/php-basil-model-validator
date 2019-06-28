<?php

namespace webignition\BasilModelValidator\Result;

class ValidResult extends AbstractResult
{
    public function __construct(object $model)
    {
        parent::__construct(true, $model);
    }
}
