<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Result;

abstract class AbstractResult implements ResultInterface
{
    private $isValid;
    private $subject;

    public function __construct(bool $isValid, $subject)
    {
        $this->isValid = $isValid;
        $this->subject = $subject;
    }

    public function getIsValid(): bool
    {
        return $this->isValid;
    }

    public function getSubject()
    {
        return $this->subject;
    }
}
