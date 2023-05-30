<?php 

namespace AjdVal\Utils;

use AjdVal\Factory;

final class Utils
{
	public const DS = DIRECTORY_SEPARATOR;

	private function __construct()
    {
    }

    public static function getShortNameClass($class, Factory\FactoryTypeEnum $suffix = Factory\FactoryTypeEnum::TYPE_RULES)
    {
    	$segments = \explode('\\', $class);
    	return \strtolower(\str_replace([$suffix->value], [''], \end($segments)));
    }

	public static function appendPropertyPath(string $basePath, string $subPath): string
    {
        if ('' !== $subPath) {
            if ('[' === $subPath[0]) {
                return $basePath.$subPath;
            }

            return '' !== $basePath ? $basePath.'.'.$subPath : $subPath;
        }

        return $basePath;
    }

    public static function injectInString(string $string, array $variables): string
    {
        if (empty($string) || empty($variables)) {
            return $string;
        }

        $placeholder = '/(?<injectables>\$\{(?:@?[a-z0-9]+[a-z0-9_\-\.]*+)(?::.+?)?\})/i';
        $injectables = preg_match_all($placeholder, $string, $matches);
        $injectables = $injectables ? $matches['injectables'] : [];

        // replace the variables in the string
        foreach ($injectables as $injectable) {
            $parts    = explode(':', trim($injectable, '{$}'), 2);
            $key      = $parts[0];
            $fallback = $parts[1] ?? null;

            $variable = self::getArrayValue($variables, $key);
            $variable = self::getInjectionVariable($variable, $fallback);
            $variable = Serializer::serialize($variable);

            // if the placeholder is wrapped with double quotes
            // it needs to be escaped to minimize CSV enclosure collisions
            $escapable = strpos($string, "'{$injectable}'") !== false;
            $variable  = $escapable ? addcslashes($variable, "'") : $variable;

            $string = strtr($string, [$injectable => $variable]);
        }

        return $string;
    }

    public static function injectInArray(array $array, array $variables): array
    {
        if (empty($array) || empty($variables)) {
            return $array;
        }

        $placeholder = '/^(?<injectables>(?:@?[a-z0-9]+[a-z0-9_\-\.]*)(?::.+?)?)$/i';
        $injectables = preg_grep($placeholder, $array) ?: [];

       
        $injectables = array_filter($injectables, 'is_string');

        foreach ($injectables as $index => $injectable) {
            $parts    = explode(':', $injectable, 2);
            $key      = $parts[0];
            $fallback = $parts[1] ?? null;

            $variable = self::getArrayValue($variables, $key);
            $variable = self::getInjectionVariable($variable, $fallback);

            $array[$index] = $variable;
        }

        return $array;
    }

   	private static function getInjectionVariable(mixed $variable, ?string $fallback): mixed
    {
        if ($fallback !== null && ($variable === null || $variable === '')) {
            return $fallback;
        }

        return $variable;
    }

    public static function expandArrayWildcards(array $array, array $data): array
    {
        foreach ($array as $key => $value) {
            $key = strval($key);

            if (strpos($key, '*') === false) {
                continue;
            }

            if ($key === '*') {
                $clone  = $data;
                $result = array_walk($clone, fn (&$item) => $item = is_object($value) ? clone $value : $value);
                $array  = array_replace_recursive($clone, $array);

                unset($array[$key], $result);

                continue;
            }

            [$before, $after] = explode('*', $key, 2);
            [$before, $after] = [rtrim($before, '.'), ltrim($after, '.')];

            $content = self::getArrayValue($data, $before, $value);
            $inserts = [];

            foreach ((array)$content as $nested => $_) {
                $wildcard = is_array($content) ? $nested : ''; // scalar values shouldn't become arrays
                $expanded = trim(sprintf('%s.%s.%s', $before, $wildcard, $after), '.');
                $value    = is_object($value) ? clone $value : $value;
                $insert   = [$expanded => $value];

                // keep expanding if key still have wildcards
                while (strpos((string)key($insert), '*') !== false) {
                    $insert = self::expandArrayWildcards($insert, $data);
                }

                $inserts[] = $insert;
            }

            $keys   = array_keys($array);
            $offset = array_search($key, $keys);
            $head   = array_slice($array, 0, $offset = intval($offset));
            $tail   = array_slice($array, $offset);
            $count  = array_unshift($inserts, $head);
            $count  = array_push($inserts, $tail);
            $array  = array_merge(...$inserts);

            unset($array[$key], $count);
        }

        return $array;
    }

    public static function getArrayValue(array $array, string $key, $fallback = null): mixed
    {
        if (empty($array)) {
            return $fallback;
        }

        $rtlKey = $key;
        
        static $ltrKey = null;

        if ($ltrKey === null) {
            $ltrKey = $key;
        }
        
        if (strpos($ltrKey, '.') === false) {
            $value = $array[$ltrKey] ?? $fallback;
            $ltrKey = null;

            return $value;
        }

        if (isset($array[$rtlKey]) === false) {
            $rtlKey = substr($rtlKey, 0, strrpos($rtlKey, '.') ?: 0);
            
            if (empty($rtlKey)) {                
                $ltrKey = null;
                return $fallback;
            }

            return self::getArrayValue($array, $rtlKey, $fallback);
        }

        if (strcmp($rtlKey, $ltrKey) === 0) {
            $value = $array[$key] ?? $fallback;
            $ltrKey = null;

            return $value;
        }

        $rest = substr($ltrKey, strlen($rtlKey) + 1);
        $ltrKey = $rest;

        return self::getArrayValue($array[$rtlKey], $rest, $fallback);
    }

    public static function castToArray(mixed $variable): array
    {
        $array = (array)($variable);
        $stack = [&$array];
        $final = &$stack[0];
        $fixes = [];

        while (!empty($stack)) {
            $depth = count($stack) - 1; 
            $array = &$stack[$depth] ?? null;

            if ($array === null) {
                break;
            }

            $key = key($array);

            if ($key === null) {
                unset($stack[$depth]);
                reset($array);

                continue;
            }

            $key   = strval($key);
            $new   = strrchr($key, "\0") ?: $key;
            $new   = trim($new);
            $value = &$array[$key];

            if ($key !== $new) {
                $fixes[] = [&$array, [$key => $new]];
            }

            if (is_object($value) || is_array($value)) {
                $array[$key]       = is_array($value) ? $value : get_mangled_object_vars($value);
                $stack[$depth + 1] = &$array[$key];
            }

            next($array);
        }

        foreach ($fixes as [&$array, $keys]) {
            $oldKey = key($keys);
            $newKey = end($keys);
            $value  = &$array[$oldKey];

            $keys   = array_keys($array);
            $offset = array_search($oldKey, $keys, true);
            $head   = array_slice($array, 0, $offset, true);
            $tail   = array_slice($array, 1 + $offset, null, true);
            
            $array  = $head + [$newKey => $value] + $tail;
        }

        return $final;
    }

    public static function interpolate(string $text, array $context = [], string $wrapper = '{}'): string
    {
        if (($length = strlen($wrapper)) && $length !== 2) {
            $wrapper = '{}';
        }

        $replacements = [];

        foreach ($context as $key => $value) {
            $placeholder = ($wrapper[0] ?? '') . $key . ($wrapper[1] ?? '');

            if (
                (is_scalar($value) || is_null($value)) ||
                (is_object($value) && method_exists($value, '__toString'))
            ) {
                $replacements[$placeholder] = strval($value === null ? 'null' : $value);

                continue;
            }

            $replacements[$placeholder] = json_encode($value);
        }

        return strtr($text, $replacements);
    }
    
    public static function transform(string $subject, string ...$transformations): string
    {
        $transliterations = 'Any-Latin;Latin-ASCII;NFD;NFC;Lower();[:NonSpacing Mark:] Remove;[:Punctuation:] Remove;[:Other:] Remove;[\u0080-\u7fff] Remove;';

        static $transformers = null;
        static $functions    = null;

        if ($transformers === null) {
            $transformers = [
                'clean'     => fn ($string) => self::transform(preg_replace(['/[^\p{L}\p{N}\s]+/u', '/[\p{Lu}]+/u', '/[\s]+/'], [' ', ' $0', ' '], $string), 'trim'),
                'alnum'     => fn ($string) => self::transform(preg_replace('/[^a-zA-Z0-9 ]+/', '', $string), 'trim'),
                'alpha'     => fn ($string) => self::transform(preg_replace('/[^a-zA-Z]+/', '', $string), 'trim'),
                'numeric'   => fn ($string) => self::transform(preg_replace('/[^0-9]+/', '', $string), 'trim'),
                'slug'      => fn ($string) => self::transform(transliterator_transliterate($transliterations, preg_replace('/-+/', ' ', $string)), 'kebab'),
                'title'     => fn ($string) => self::transform($string, 'clean', 'ucwords'),
                'sentence'  => fn ($string) => self::transform($string, 'clean', 'lower', 'ucfirst'),
                'pascal'    => fn ($string) => self::transform($string, 'clean', 'lower', 'ucwords', 'spaceless'),
                'camel'     => fn ($string) => self::transform($string, 'clean', 'lower', 'ucwords', 'lcfirst', 'spaceless'),
                'dot'       => fn ($string) => strtr(self::transform($string, 'clean', 'lower'), [' ' => '.']),
                'kebab'     => fn ($string) => strtr(self::transform($string, 'clean', 'lower'), [' ' => '-']),
                'snake'     => fn ($string) => strtr(self::transform($string, 'clean', 'lower'), [' ' => '_']),
                'constant'  => fn ($string) => strtr(self::transform($string, 'clean', 'upper'), [' ' => '_']),
                'cobol'     => fn ($string) => strtr(self::transform($string, 'clean', 'upper'), [' ' => '-']),
                'train'     => fn ($string) => strtr(self::transform($string, 'clean', 'lower', 'ucwords'), [' ' => '-']),
                'lower'     => fn ($string) => mb_strtolower($string, 'UTF-8'),
                'upper'     => fn ($string) => mb_strtoupper($string, 'UTF-8'),
                'spaceless' => fn ($string) => preg_replace('/[\s]+/', '', $string),
            ];

            $functions = ['strtolower', 'strtoupper', 'lcfirst', 'ucfirst', 'ucwords', 'trim', 'ltrim', 'rtrim'];
        }

        foreach ($transformations as $transformation) {
            $name = strtolower($transformation);

            if (array_key_exists($name, $transformers)) {
                $subject = ($transformers[$name])($subject);

                continue;
            }

            if (in_array($name, $functions)) {
                $subject = ($name)($subject);
            }
        }

        return $subject;
    }
}