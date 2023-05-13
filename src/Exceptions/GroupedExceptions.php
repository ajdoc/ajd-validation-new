<?php 

namespace AjdVal\Exceptions;

class GroupedExceptions extends NestedExceptions
{
    const NONE = 0;
    const SOME = 1;

    public static $defaultMessages = [
        self::ERR_DEFAULT => [
            self::NONE => 'All of the required rules must pass for :field.',
            self::SOME => 'These rules must pass for :field.'
        ],
        self::ERR_NEGATIVE => [
            self::NONE => 'None of there rules must pass for :field.',
            self::SOME => 'These rules must not pass for :field.',
        ],
    ];
}