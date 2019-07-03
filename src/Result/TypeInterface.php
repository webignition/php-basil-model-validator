<?php

namespace webignition\BasilModelValidator\Result;

interface TypeInterface
{
    const NOT_APPLICABLE = 0;
    const UNHANDLED = 1;
    const ACTION = 2;
    const IDENTIFIER = 3;
    const VALUE = 4;
    const ASSERTION = 5;
    const PAGE = 6;
}
