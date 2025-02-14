<?php

namespace AjdVal\Expression;

use AjdVal\Traits\SmartEnumTrait;

enum ExpressionOperator: string
{
    use SmartEnumTrait;

    case Not   = '!';

    case And   = '&&';

    case Or    = '||';

    case Xor   = 'xor';

    case Open  = '(';

    case Close = ')';
}