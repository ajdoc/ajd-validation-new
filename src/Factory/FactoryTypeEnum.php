<?php

namespace AjdVal\Factory;

enum FactoryTypeEnum: string
{
    case TYPE_RULES = 'Rule';
    case TYPE_RULE_HANDLERS = 'RuleHandler';
    case TYPE_RULE_EXCEPTIONS = 'RuleException';
    case TYPE_FILTERS = 'Filter';
    case TYPE_LOGICS = 'Logic';
}
