<?php

namespace AjdVal\Utils;

class LogicComparator
{
     public static $operators = [
        '=='  => [self::class, 'isEqual'],
        'eq'  => [self::class, 'isEqual'],
        '!='  => [self::class, 'isNotEqual'],
        '<>'  => [self::class, 'isNotEqual'],
        'neq' => [self::class, 'isNotEqual'],
        '===' => [self::class, 'isIdentical'],
        'id'  => [self::class, 'isIdentical'],
        '!==' => [self::class, 'isNotIdentical'],
        'nid' => [self::class, 'isNotIdentical'],
        '<'   => [self::class, 'isLessThan'],
        'lt'  => [self::class, 'isLessThan'],
        '<='  => [self::class, 'isLessThanOrEqual'],
        'lte' => [self::class, 'isLessThanOrEqual'],
        '>'   => [self::class, 'isGreaterThan'],
        'gt'  => [self::class, 'isGreaterThan'],
        '>='  => [self::class, 'isGreaterThanOrEqual'],
        'gte' => [self::class, 'isGreaterThanOrEqual'],
        '<=>' => [self::class, 'doSpaceship'],
        'sps' => [self::class, 'doSpaceship'],
        '!'   => [self::class, 'doNegate'],
        '&&'  => [self::class, 'doAnd'],
        'and' => [self::class, 'doAnd'],
        '||'  => [self::class, 'doOr'],
        'or'  => [self::class, 'doOr'],
        'xor' => [self::class, 'doXor'],
        '~'   => [self::class, 'doBitwiseNegate'],
        '&'   => [self::class, 'doBitwiseAnd'],
        '|'   => [self::class, 'doBitwiseOr'],
        '^'   => [self::class, 'doBitwiseXor'],
        '<<'  => [self::class, 'doBitwiseShiftLeft'],
        '>>'  => [self::class, 'doBitwiseShiftRight'],
    ];    

   
    public static function isEqual(mixed $value1, mixed $value2): bool
    {
        return ($value1 == $value2);
    }

   
    public static function isNotEqual(mixed $value1, mixed $value2): bool
    {
        return ($value1 != $value2);
    }

    
    public static function isIdentical(mixed $value1, mixed $value2): bool
    {
        return ($value1 === $value2);
    }

    
    public static function isNotIdentical(mixed $value1, mixed $value2): bool
    {
        return ($value1 !== $value2);
    }

    
    public static function isLessThan(mixed $value1, mixed $value2): bool
    {
        return ($value1 < $value2);
    }

    
    public static function isLessThanOrEqual(mixed $value1, mixed $value2): bool
    {
        return ($value1 <= $value2);
    }

    
    public static function isGreaterThan(mixed $value1, mixed $value2): bool
    {
        return ($value1 > $value2);
    }

   
    public static function isGreaterThanOrEqual(mixed $value1, mixed $value2): bool
    {
        return ($value1 >= $value2);
    }

    
    public static function doSpaceship(mixed $value1, mixed $value2): int
    {
        return ($value1 <=> $value2);
    }

    public static function doNegate($value): bool
    {
        return !($value);
    }

    public static function doAnd(mixed $value1, mixed $value2): bool
    {
        return ($value1 && $value2);
    }

  
    public static function doOr(mixed $value1, mixed $value2): bool
    {
        return ($value1 || $value2);
    }

    public static function doXor(mixed $value1, mixed $value2): bool
    {
        return ($value1 xor $value2);
    }


    public static function doBitwiseNegate(null|bool|int|float|string $value): int
    {
        return ~((int)$value);
    }

  
    public static function doBitwiseAnd(null|bool|int|float|string $value1, null|bool|int|float|string $value2): int
    {
        return ($value1 & $value2);
    }

    public static function doBitwiseOr(null|bool|int|float|string $value1, null|bool|int|float|string $value2): int
    {
        return ($value1 | $value2);
    }

   
    public static function doBitwiseXor(null|bool|int|float|string $value1, null|bool|int|float|string $value2): int
    {
        return ($value1 ^ $value2);
    }

    public static function doBitwiseShiftLeft(null|bool|int|float|string $value1, null|bool|int|float|string $value2): int
    {
        return ($value1 << $value2);
    }

   
    public static function doBitwiseShiftRight(null|bool|int|float|string $value1, null|bool|int|float|string $value2): int
    {
        return ($value1 >> $value2);
    }


    public static function isEmpty(mixed $variable): bool
    {
        if (is_bool($variable)) {
            return false;
        }

        if (is_numeric($variable)) {
            return false;
        }

        if (is_string($variable)) {
            return empty(trim($variable));
        }

        return empty($variable);
    }

    public static function getCount(mixed $variable): int|float
    {
        if (is_countable($variable)) {
            return count($variable);
        }

        if (is_object($variable)) {
            return count(get_object_vars($variable));
        }

        if (is_bool($variable)) {
            return intval($variable);
        }

        if (is_numeric($variable)) {
            return floatval($variable);
        }

        return mb_strlen(trim(strval($variable)));
    }

    public static function return(mixed $value): mixed
    {
        return $value;
    }
}