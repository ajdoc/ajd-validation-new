<?php 

namespace AjdVal\Attributes;

use AjdVal\Traits;
use AjdVal\Contracts\RuleCreatorInterface;
use AjdVal\Contracts\RuleInterface;

class RuleCreate implements RuleCreatorInterface
{
	use Traits\RuleCreatorTrait;
	
	protected array $arguments;

	public function __construct(protected string $qualifiedClass, ...$arguments)
	{
		$this->arguments[] = $qualifiedClass;
		$this->arguments = array_merge($this->arguments, $arguments);
	}

	public function extractRule(): RuleInterface
	{
		return static::createRule(...$this->arguments);
	}
}