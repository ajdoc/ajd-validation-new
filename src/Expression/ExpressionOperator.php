<?php

namespace AjdVal\Expression;

use AjdVal\Traits\SmartEnumTrait;

enum ExpressionOperator: string
{
    use SmartEnumTrait;

    case Not   = '~';

    case And   = '&';

    case Or    = '|';

    case Xor   = '^';

    case Open  = '(';

    case Close = ')';
}