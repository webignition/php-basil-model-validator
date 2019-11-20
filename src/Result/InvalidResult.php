<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Result;

class InvalidResult extends AbstractResult implements InvalidResultInterface
{
    private $type;
    private $reason;
    private $previous;
    private $context = [];

    public function __construct($subject, string $type, string $reason, ?InvalidResultInterface $previous = null)
    {
        parent::__construct(false, $subject);

        $this->type = $type;
        $this->reason = $reason;
        $this->previous = $previous;
    }

    public static function createUnhandledSubjectResult($subject): InvalidResultInterface
    {
        return new InvalidResult($subject, TypeInterface::UNHANDLED, '');
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getPrevious(): InvalidResultInterface
    {
        return $this->previous;
    }

    public function withContext(array $context): InvalidResultInterface
    {
        $new = clone $this;
        $new->context = $context;

        return $new;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
