<?php

namespace webignition\BasilModelValidator\Result;

class InvalidIdentifierResult extends InvalidResult
{
    private $pageProperty;
    private $browserProperty;

    public function __construct(object $model, int $code = 0, string $message = '')
    {
        parent::__construct($model, TypeInterface::IDENTIFIER, $code, $message);
    }

    public function setPageProperty(string $pageProperty)
    {
        $this->pageProperty = $pageProperty;
    }

    public function getPageProperty(): ?string
    {
        return $this->pageProperty;
    }

    public function setBrowserProperty(string $browserProperty)
    {
        $this->browserProperty = $browserProperty;
    }

    public function getBrowserProperty(): ?string
    {
        return $this->browserProperty;
    }
}
