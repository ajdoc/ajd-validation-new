<?php 

namespace AjdVal\Handlers;

use Stringable;

class RequiredRuleHandler extends AbstractHandlers
{
	public function __construct(Stringable|string $message = '')
	{
		$this->message = $message;
	}
}