<?php

namespace AjdVal\Expression;

use AjdVal\Traits\SmartEnumTrait;

enum ExpressionStrategy
{
    use SmartEnumTrait;

    case FailFast;

    case FailLazy;
}
