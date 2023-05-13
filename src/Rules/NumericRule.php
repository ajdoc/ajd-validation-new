<?php 

namespace AjdVal\Rules;

class NumericRule extends AbstractCallback
{
	public function __construct()
	{
		parent::__construct('is_numeric');
	}
}