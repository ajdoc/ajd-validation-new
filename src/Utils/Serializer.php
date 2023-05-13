<?php

namespace AjdVal\Utils;

class Serializer
{
   
    public static function serialize(mixed $variable): string
    {
        if (is_object($variable) && method_exists($variable, '__toString')) {
            $variable = static::encode(strval($variable));
        } else {
            $variable = static::encode($variable);
        }

        $variable = strval($variable); // in case JSON encoding fails

        return $variable;
    }

    
    public static function unserialize(string $variable, ?string $cast = null): mixed
    {
        static $casts = null;

        if ($casts === null) {
            $casts = [
                'null'    => fn (&$var) => settype($var, 'null'),
                'bool'    => fn (&$var) => settype($var, 'boolean'),
                'boolean' => fn (&$var) => settype($var, 'boolean'),
                'int'     => fn (&$var) => settype($var, 'integer'),
                'integer' => fn (&$var) => settype($var, 'integer'),
                'float'   => fn (&$var) => settype($var, 'float'),
                'double'  => fn (&$var) => settype($var, 'float'),
                'string'  => fn (&$var) => settype($var, 'string'),
                'array'   => fn (&$var) => settype($var, 'array'),
                'object'  => fn (&$var) => settype($var, 'object'),
            ];
        }

        $variable = static::decode($variable);

        if (isset($casts[$cast = strtolower(strval($cast))])) {
            ($casts[$cast])($variable);
        }

        return $variable;
    }

    public static function encode(mixed $value): string
    {
        return strval(json_encode($value, (
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE |
            JSON_PARTIAL_OUTPUT_ON_ERROR
        )));
    }

    public static function decode(string $value): mixed
    {
        return $value === 'null' ? null : (
            json_decode($value, true) ??
            json_decode(stripslashes($value), true) ??
            json_decode(sprintf('"%s"', $value), true) ??
            $value
        );
    }
}