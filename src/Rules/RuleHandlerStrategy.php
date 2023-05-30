<?php

namespace AjdVal\Rules;

use AjdVal\Traits\SmartEnumTrait;

enum RuleHandlerStrategy
{
    use SmartEnumTrait;

    case AutoCreate;

    case NotAutoCreate;
}
