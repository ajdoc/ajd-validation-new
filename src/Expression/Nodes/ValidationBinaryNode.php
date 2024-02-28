<?php

namespace AjdVal\Expression\Nodes;

use Ajd\Expression\Compiler\Compiler;
use Ajd\Expression\Lexer\SyntaxError;
use Ajd\Expression\Nodes\BinaryNode;
use AjdVal\Expression\Traits;
use AjdVal\Expression\ExpressionValidationErrorMessages;

class ValidationBinaryNode extends BinaryNode
{
    use Traits\RuleNodeTrait;

    public function evaluate(array $functions, array $values): mixed
    {
        $operator = $this->attributes['operator'];
        $leftRuleInstance = null; 
        $rightRuleInstance = null;
        $righReversed = false;
        $leftReversed = false;

        $left = $this->nodes['left']->evaluate($functions, $values);

        if (method_exists($this->nodes['left'], 'getRuleInstance')) {
            $leftRuleInstance = $this->nodes['left']->getRuleInstance();
            $leftReversed = $this->nodes['left']->isReversed();
        }

        if (isset(self::FUNCTIONS[$operator])) {

            $right = $this->nodes['right']->evaluate($functions, $values);

            if (method_exists($this->nodes['right'], 'getRuleInstance')) {
                $rightRuleInstance = $this->nodes['right']->getRuleInstance();
                $righReversed = $this->nodes['right']->isReversed();
            }

            return match ($operator) {
                'in' => \in_array($left, $right, true),
                'not in' => !\in_array($left, $right, true),
                default => self::FUNCTIONS[$operator]($left, $right),
            };
        }

        $right = $this->nodes['right']->evaluate($functions, $values);

        if (method_exists($this->nodes['right'], 'getRuleInstance')) {
            $rightRuleInstance = $this->nodes['right']->getRuleInstance();
            $righReversed = $this->nodes['right']->isReversed();

            if (! empty($this->rightRuleInstance)) {
                $this->setRuleInstance($rightRuleInstance);    
            }
            
        }

        $operatorResult = null;
        
        switch ($operator) {
            case 'or':
            case '||':
                $operatorResult = ($left || $right);
            break;
            case 'and':
            case '&&':
                $operatorResult = ($left && $right);
            break;
            case 'xor':
                $operatorResult = ($left xor $right);
            break;
        }

        if (! is_null($operatorResult)) {

            ExpressionValidationErrorMessages::createErrorMessages(
                [
                    'instance' => $leftRuleInstance,
                    'reversed' => $leftReversed,
                    'result' => $left,
                ],
                [
                    'instance' => $rightRuleInstance,
                    'reversed' => $righReversed,  
                    'result' => $right,
                ],
                [
                    'operator' => $operator,
                    'result' => $operatorResult
                ]
            );

            return $operatorResult;
        }

        $right = $this->nodes['right']->evaluate($functions, $values);

        if (method_exists($this->nodes['right'], 'getRuleInstance')) {
            $rightRuleInstance = $this->nodes['right']->getRuleInstance();
            $righReversed = $this->nodes['right']->isReversed();
        }

        switch ($operator) {
            case '|':
                return $left | $right;
            case '^':
                return $left ^ $right;
            case '&':
                return $left & $right;
            case '==':
                return $left == $right;
            case '===':
                return $left === $right;
            case '!=':
                return $left != $right;
            case '!==':
                return $left !== $right;
            case '<':
                return $left < $right;
            case '>':
                return $left > $right;
            case '>=':
                return $left >= $right;
            case '<=':
                return $left <= $right;
            case '<=>':
                return $left <=> $right;
            case 'not in':
                return !\in_array($left, $right, true);
            case 'in':
                return \in_array($left, $right, true);
            case '+':
                return $left + $right;
            case '-':
                return $left - $right;
            case '~':
                return $left.$right;
            case '*':
                return $left * $right;
            case '/':
                if (0 == $right) {
                    throw new \DivisionByZeroError('Division by zero.');
                }

                return $left / $right;
            case '%':
                if (0 == $right) {
                    throw new \DivisionByZeroError('Modulo by zero.');
                }

                return $left % $right;
            case 'matches':
                return $this->evaluateMatches($right, $left);
        }
    }
}
