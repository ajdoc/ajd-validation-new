<?php

namespace AjdVal\Expression\Traits;

use Throwable;
use Stringable;
use Closure;
use InvalidArgumentException;

use AjdVal\Utils\Utils;
use AjdVal\Expression\ExpressionOperator;

trait ExpressionBuilderTrait
{
	/**
     * Expression operators.
     *
     * @var array<string,ExpressionOperator>
     */
	public static $OPERATORS = [
        'NOT' => ExpressionOperator::Not,
        'AND' => ExpressionOperator::And,
        'OR' => ExpressionOperator::Or,
        'XOR' => ExpressionOperator::Xor,
        'OPEN' => ExpressionOperator::Open,
        'CLOSE' => ExpressionOperator::Close,
    ];

	/**
     * Expression string buffer.
     *
     * @var string
     */
    protected string $buffer = '';

    /**
     * @return array<string,string>
     */
    private function getOperatorChars(): array
    {
        static $operators = null;

        if ($operators === null) {
            $operators = [
                'opening' => implode('', [
                    self::$OPERATORS['OPEN']->toScalar(),
                ]),
                'closing' => implode('', [
                    self::$OPERATORS['CLOSE']->toScalar(),
                ]),
                'combining' => implode('', [
                    self::$OPERATORS['AND']->toScalar(),
                    self::$OPERATORS['OR']->toScalar(),
                    self::$OPERATORS['XOR']->toScalar(),
                ]),
                'and_comma' => implode('', [
                    self::$OPERATORS['AND']->toScalar(),
                    ',',
                ]),
                'all' => implode('', ExpressionOperator::values()),
            ];
        }

        return $operators;
    }

     /**
     * Provides rules and aliases as class methods.
     *
     * @param string $name
     * @param mixed[] $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        $isRule = $arguments['isRule'] ?? false;

        unset($arguments['isRule']);

        $this->write(static::createRuleName($name, $arguments, $isRule));

        return $this;
    }

    /**
     * Returns the current expression string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->build();
    }

    public function reset(): void
    {
        $this->buffer = "";
    }

    public function write(string $expression): static 
    {
    	$operators = $this->getOperatorChars();
        $current = trim($expression);
        $last = strval(substr($buffer = trim($this->buffer), -1));
        
        if (strlen($buffer) > 0 && (
            // (1) if the current string is not an operator "~&|^()"
            strpos($operators['all'], $current) === false &&
            // (1) and the last character is not an operator "~&|^()"
            strpos($operators['all'], $last) === false ||
            // (2) or the current string is an opening operator "~("
            strpos($operators['opening'], $current) !== false &&
            // (2) and the last character is not a combining operator "&|^"
            strpos($operators['combining'], $last) === false ||
            // (3) or the last character is a closing operator ")"
            strpos($operators['closing'], $last) !== false &&
            // (3) and the current string is not an operator "~&|^()"
            strpos($operators['all'], $current) === false
        )) {

            if(
                strpos($operators['and_comma'], $last) === false
            ) {

                $this->buffer .= ' '.$operator = self::$OPERATORS['AND']->toScalar();
            }
        }

        return $this->concat($current);
    }

    public function concat(string $string): static
    {
        $this->buffer .= ' '.$string;

        return $this;
    }

 	public function not(): static
    {
        $this->write(self::$OPERATORS['NOT']->value);

        return $this;
    }

 	public function and(): static
    {
        $this->write(self::$OPERATORS['AND']->value);

        return $this;
    }

    /**
     *
     * @return static
     */
    public function or(): static
    {
        $this->write(self::$OPERATORS['OR']->value);

        return $this;
    }

    /**
     *
     * @return static
     */
    public function xor(): static
    {
        $this->write(self::$OPERATORS['XOR']->value);

        return $this;
    }

    /**
     * Adds OPEN operator (opening parenthesis: `(`). Starts a new group.
     *
     * @return static
     */
    public function open(): static
    {
        $this->write(self::$OPERATORS['OPEN']->value);

        return $this;
    }

    /**
     * Adds CLOSE operator (closing parenthesis: `)`). Ends the current group.
     *
     * @return static
     */
    public function close(): static
    {
        $this->write(self::$OPERATORS['CLOSE']->value);

        return $this;
    }

    protected function baseIf(string $type, bool|string|callable|null $evaluator = null): static
    {
        $expression = match ($type) {
            'if' => 'when(',
            'elseif' => ',when(',
            'endif' => ')'
        };

        if ($type == 'endif') {
            preg_match_all('/when\(/', $this->buffer, $matches);
            $countOpenWhen = count($matches[0] ?? []);

            $expression = str_repeat(')', $countOpenWhen);
        }

        if (is_null($evaluator)) {

            match ($type) {
                'elseif', 'endif' => $this->concat($expression),
                default => $this->write($expression)
            };

            return $this;    
        }

        if (is_callable($evaluator)) {
            $evaluator = $evaluator($this);
        }

        if (empty($evaluator)) {
            $evaluator = 'false';
        }
        
        match ($type) {
            'elseif' => $this->concat($expression.(string) $evaluator.','),
            default => $this->write($expression.(string) $evaluator.',')
        };

        return $this;    
    }

    public function else(): static
    {
        $this->concat(',');

        return $this;
    }

    public function then(): static
    {
        $this->concat(',');

        return $this;
    }

    public function if(bool|string|callable|null $evaluator = null): static
    {
        return $this->baseIf('if', $evaluator);
    }

    public function elseif(bool|string|callable|null $evaluator = null): static
    {
        return $this->baseIf('elseif', $evaluator);
    }

    public function endif(): static
    {
        return $this->baseIf('endif');
    }

    /**
     * Returns the current expression string.
     *
     * @return string
     *
     * @throws InvalidArgumentException If the expression string is invalid.
     */
    public function build(): string
    {
        $expression = $this->buffer;

        return $expression;
    }

    public static function createRuleName(string $name, array $arguments, bool $isRule): string
    {
        $arguments = array_filter($arguments, 'is_scalar');

        foreach ($arguments as $key => $value) {
            if (is_string($value)) {
                $arguments[$key] = "'$value'";
            }
        }
        
        if ($isRule) {
            return $name.'('.implode(',', $arguments).')';
        }

        return $name;
    }    
}