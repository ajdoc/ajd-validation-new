<?php 

namespace AjdVal\Rules;

use AjdVal\Handlers\HandlerDto;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class NumericRule extends AbstractCallback
{
	public function __construct(HandlerDto|null $handler = null)
	{
		$this->initHandler($handler, func_get_args());

		parent::__construct('is_numeric');
	}
}