<?php 

namespace AjdVal\Traits;

use DateTime;
use Traversable;
use Exception;

trait ErrorsTrait 
{
	protected static $maxReplacementOfString = '...';
	protected static $maxDepthOfString = 5;
    protected static $maxCountOfString = 10;

	public function replaceErrorPlaceholder(array $message_details, string $message): string
	{
		$newMessage = static::formatError($message_details, $message);
		$newMessage = static::formatError($message_details, $newMessage, '/:(\w+)/');

		return $newMessage;
	}

	public static function formatError(array $message_details, string $message, string $pattern = '/{(\w+)}/', bool $jsonEncode = true): string
	{
		if (is_object($message)) {
			return '';
		}

		$newMessage = preg_replace_callback(
           $pattern,
            function ($match) use (&$message_details, $jsonEncode) {
     			
                if (!isset($message_details[$match[1]])) {
                    return $match[0];
                }

                $real_match = $match[0];

                if (isset($match[1])) {
                	$real_match = $match[1];
                }
                
                if( isset($message_details[$match[1] ])) {
            		$value = $message_details[$match[1]];	
                }
                
                if ('name' == $real_match && is_string($value)) {
                    return $value;
                }

                return static::stringify($value, 1, $jsonEncode);
            },
            $message
        );

        return $newMessage;
	}

	public static function stringify(mixed $value, int $depth = 1, $jsonEncode = true): string
    {
        if ($depth >= static::$maxDepthOfString) {
            return static::$maxReplacementOfString;
        }

        if (is_array($value)) {
    		return static::stringifyArray($value, $depth);
        }

        if (is_object($value)) {
            return static::stringifyObject($value, $depth);
        }

        if (is_resource($value)) {
            return sprintf('`[resource] (%s)`', get_resource_type($value));
        }

        if (is_float($value)) {
        	if (is_infinite($value)) {
        		return ($value > 0 ? '' : '-').'INF';
        	}

        	if (is_nan($value)) {
        		return 'NaN';
        	}
        }

        if (! $jsonEncode) {
        	return $value;
        }

        return (@json_encode($value, (JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?: $value);
    }

    public static function stringifyArray(array $value, int $depth = 1): string
    {
        $nextDepth = ($depth + 1);

        if ($nextDepth >= static::$maxDepthOfString) {
            return  static::$maxReplacementOfString;
        }

        if (empty($value)) {
             return '{ }';
        }

     	$total = count($value);
        $string = '';
        $current = 0;

        foreach ($value as $key => $val) {
	 		if ($current++ >= static::$maxCountOfString) {
		 		$string .= static::$maxReplacementOfString;
		 		break;
	 		}

 		 	if (!is_int($key)) {
 				$string .= sprintf('%s: ', static::stringify($key, $nextDepth));
 			}

 			$string .= static::stringify($val, $nextDepth);

 			if ($current !== $total) {
		 		$string .= ', ';
 			}
        }

     	return sprintf('{ %s }', $string);
    }

    public static function stringifyObject(mixed $value, int $depth = 2): string
    {
    	$nextDepth = $depth + 1;

    	if ($value instanceof DateTime) {
    		return sprintf('"%s"', $value->format('Y-m-d H:i:s'));
    	}

    	$class = get_class($value);

    	if ($value instanceof Traversable) {
    		return sprintf('`[traversable] (%s: %s)`', $class, static::stringify(iterator_to_array($value), $nextDepth));
    	}

    	if ($value instanceof Exception) 
    	{
    		$errProp = [
		 		'message' => $value->getMessage(),
		 		'code' => $value->code(),
		 		'file' => $value->getFile().':'.$value->getLine(),
		 		'trace' => $value->getTraceAsString()
    		];

    		return sprintf('`[exception] (%s: %s)`', $class, static::stringify($errProp, $nextDepth));
    	}

    	if (method_exists($value, '__toString')) {
    		return static::stringify($value->__toString(), $nextDepth);
    	}

    	$errProp = static::stringify(get_object_vars($value), $nextDepth);

    	return sprintf('`[object] (%s: %s)`', $class, str_replace('`', '', $errProp));
    }
}