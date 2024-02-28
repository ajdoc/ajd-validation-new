<?php

namespace AjdVal\Expression;

use Ajd\Expression\Expression;
use Ajd\Expression\Parser\ParserInterface;
use Ajd\Expression\Engine\Expr;
use Ajd\Expression\Compiler\Compiler;
use AjdVal\Expression\Parsers;
use AjdVal\Contracts;

class ExpressionValidation extends Expression
{
    protected ParserInterface $parser;

    protected array $rules = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function getCompiler(): Compiler
    {
        $this->compiler ??= new Compiler(array_merge($this->functions, $this->rules));

        return $this->compiler->reset();
    }  

    public function getParser(): ParserInterface
    {
        return $this->parser ??= new Parsers\ExpressionParserValidation($this->functions, $this->rules);
    }

    public function evaluate(Expr|string $expression, array $values = []): mixed
    {
        return $this->parse($expression, array_keys($values))->getNodes()->evaluate(array_merge($this->functions, $this->rules), $values);
    }

    public function registerRule(string $name, Contracts\RuleInterface $rule, callable $compiler, callable $evaluator): void
    {
        if (isset($this->parser)) {
            throw new \LogicException('Registering functions after calling evaluate(), compile() or parse() is not supported.');
        }

        $this->rules[$name] = ['instance' => $rule, 'compiler' => $compiler, 'evaluator' => $evaluator];

        if (isset($this->functions['when'])) {
            $this->rules['when'] = [
                'instance' => null, 
                'compiler' => $this->functions['when']['compiler'], 
                'evaluator' => $this->functions['when']['evaluator']
            ];
        }
    }
}