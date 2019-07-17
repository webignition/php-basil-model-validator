<?php

namespace webignition\BasilModelValidator\Result;

interface TypeInterface
{
    const NOT_APPLICABLE = 'not-applicable';
    const UNHANDLED = 'unhandled';
    const ACTION = 'action';
    const IDENTIFIER = 'identifier';
    const VALUE = 'value';
    const ASSERTION = 'assertion';
    const PAGE = 'page';
    const DATA_SET = 'step';
}
