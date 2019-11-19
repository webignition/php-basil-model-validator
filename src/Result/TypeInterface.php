<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Result;

interface TypeInterface
{
    public const NOT_APPLICABLE = 'not-applicable';
    public const UNHANDLED = 'unhandled';
    public const ACTION = 'action';
    public const IDENTIFIER = 'identifier';
    public const VALUE = 'value';
    public const ASSERTION = 'assertion';
    public const PAGE = 'page';
    public const DATA_SET = 'data-set';
    public const STEP = 'step';
    public const TEST_CONFIGURATION = 'test-configuration';
    public const TEST = 'test';
    public const TEST_SUITE = 'test-suite';
}
