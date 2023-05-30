<?php 

namespace AjdVal\Rules;


#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RequiredRule extends AbstractRule
{
	public function validate(mixed $value, string $path = ''): bool
	{
		return ! empty($value);
	}
}