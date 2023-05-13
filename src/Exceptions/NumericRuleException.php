<?php 

namespace AjdVal\Exceptions;

class NumericRuleException extends ValidationExceptions
{
 	public static $defaultMessages = [
        self::ERR_DEFAULT => [
            self::STANDARD => '{field} must be numeric.',
        ],
        self::ERR_NEGATIVE => [
            self::STANDARD => '{field} must not be numeric.',
        ],
    ];
}