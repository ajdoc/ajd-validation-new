<?php 

namespace AjdVal\Rules;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RequiredRule extends AbstractRule
{
	public $t;
	public $m;
	public function __construct(mixed $options = null) {
		
		/*$cnt = func_num_args();

		if ($cnt === 0) {
			$options = null;
		} else if ($cnt === 1 && is_array($options)) {
			$options = array_key_exists('value', $options) ? [$options['value']] : $options;
		} else if ($cnt > 1) {
			$options = func_get_args();
		}

		if (!is_array($options)) {
			$options = [$options];
		}

		parent::__construct($options);

		var_dump($this->t);*/
	}

	public function validate(mixed $value, string $path = ''): bool
	{
		return ! empty($value);
	}
}