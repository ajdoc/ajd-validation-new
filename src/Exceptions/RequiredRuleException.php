<?php 

namespace AjdVal\Exceptions;

class RequiredRuleException extends ValidationExceptions
{
	public static $defaultMessages = [
		 self::ERR_DEFAULT => [
		 	self::STANDARD => 'The :field field is required',
		 ],
		  self::ERR_NEGATIVE => [
            self::STANDARD => 'The :field field is not required.',
        ]
	];
}