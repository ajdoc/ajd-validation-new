<?php

namespace AjdVal\Exceptions;

class AllRuleException extends NestedExceptions
{
    public static $defaultMessages = [
        self::ERR_DEFAULT => [
            self::STANDARD => 'All of the required rules must pass for {field}.',
            // self::EXTRA          => ':field not All".',
        ],
        self::ERR_NEGATIVE => [
            self::STANDARD => 'None of these rules must pass for {field}.',
            // self::EXTRA          => ':field not All".',
        ],
    ];
}