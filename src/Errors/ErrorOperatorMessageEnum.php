<?php

namespace AjdVal\Errors;

use AjdVal\Traits\SmartEnumTrait;

enum ErrorOperatorMessageEnum: string
{
    use SmartEnumTrait;

    case AND = 'All of the rule(s) must pass.';
    case OR = 'Either of The rule(s) must pass.';
    case XOR = 'Either of The rule(s) must pass but not all.';
}