<?php

namespace AjdVal\Expression;

use AjdVal\Traits\SmartEnumTrait;

enum ExpressionBehavior: string
{
    use SmartEnumTrait;

    case Normal      = '';

    case Optimistic  = '?';

    case Pessimistic = '!';
}