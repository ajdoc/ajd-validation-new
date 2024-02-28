<?php

namespace AjdVal\Expression;

use Stringable;
use Closure;
use Throwable;
use Exception;
use InvalidArgumentException;

use AjdVal\Utils\Utils;
use AjdVal\Utils\LogicComparator;
use AjdVal\Errors\ErrorHandling;
use AjdVal\Contracts\RuleInterface;

class ExpressionRuleCreator extends ExpressionDefinition implements Stringable
{

    /**
     * Rule statement string.
     *
     * @var string
     */
    protected string $statement = '';

    /**
     * Rule input value.
     *
     * @var mixed
     */
    protected mixed $input = null;

    protected RuleInterface|null $ruleInstance = null;


    /**
     * Rule constructor.
     *
     * @param array<string,string|array<mixed>|callable> $definition [optional] Rule definition.
     * @param string $statement [optional] Rule statement.
     * @param mixed $input [optional] Rule input.
     */
    public function __construct(?array $definition = null, ?string $statement = null, mixed $input = null)
    {
        if (!empty($definition)) {
            $this->setDefinition($definition);
        }

        if (!empty($statement)) {
            $this->setStatement($statement);
        }

        if (!empty($input)) {
            $this->setInput($statement);
        }
    }

    /**
     * Executes the rule when the object is invoked as a function.
     *
     * @param mixed $input Rule input.
     *
     * @return mixed
     */
    public function __invoke(mixed $input): mixed
    {
        return $this->execute($input);
    }

    /**
     * Returns the current rule statement.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getStatement();
    }

    /**
     * Asserts that the rule statement is valid.
     *
     * @param string $statement Rule statement.
     *
     * @return void
     *
     * @throws InvalidRuleStatementException If the rule statement is invalid.
     */
    protected function assertStatementIsValid(string $statement): void
    {
        if (empty(trim($statement))) {
            throw new InvalidArgumentException(
                'Rule statement cannot be an empty string'
            );
        }
    }

    /**
     * Gets rule statement string.
     *
     * @return string
     */
    public function getStatement(): string
    {
        return $this->statement;
    }

    /**
     * Sets rule statement string.
     *
     * @param string $statement Rule statement.
     *
     * @return static
     *
     * @throws InvalidRuleStatementException If the rule statement is invalid.
     */
    public function setStatement(string $statement): static
    {
        $this->assertStatementIsValid($statement);

        $this->statement = $statement;

        return $this;
    }

    /**
     * Gets rule instance.
     *
     * @return string
     */
    public function getRuleInstance(): RuleInterface
    {
        return $this->ruleInstance;
    }

    /**
     * Sets rule Instance.
     *
     * @param RuleInterface $rule.
     *
     * @return static
     *
     */
    public function setRuleInstance(RuleInterface $rule): static
    {
        $this->ruleInstance = $rule;

        return $this;
    }

    /**
     * Gets rule input.
     *
     * @return mixed
     */
    public function getInput(): mixed
    {
        return $this->input;
    }

    /**
     * Sets rule input.
     *
     * @param mixed $input Rule input.
     *
     * @return static
     */
    public function setInput(mixed $input): static
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Executes the rule against some input (the current one or the passed one).
     *
     * @param mixed $input [optional] The input that the rule will be executed against.
     *
     * @return mixed
     *
     * @throws InexecutableRuleException If callback execution failed.
     *      The execution may fail if an exception was thrown
     *      or any type of error was raised (E_ALL) while executing the callback.
     */
    public function execute(mixed $input = null): mixed
    {
        $rule       = $this;
        $input    ??= $this->getInput();
        $statement  = $this->getStatement();

        $name       = $this->getName();
        $arguments  = $this->getArguments();
        $callback   = $this->getCallback();
        $parameters = $this->getParameters();
        $comparison = $this->getComparison();

        $symbol = ExpressionEngine::parseRule($statement, $arguments);

        $name      = $symbol['name'];
        $arguments = $symbol['arguments'];

        $parameters = Utils::injectInArray($parameters, [
            '@rule'      => $rule,
            '@name'      => $name,
            '@arguments' => $arguments,
            '@input'     => $input,
        ]);

        $output = null;

        try {
            ErrorHandling::handle(
                function () use (&$callback, &$parameters, &$output) {
                    $output = $callback(...$parameters);
                },
                "'{$name}' rule execution failed"
            );
        } catch (Throwable $error) {
            throw new ErrorHandling($error->getMessage(), $error->getCode(), $error);
        }

        // unite current variables with definition variables
        $this->setVariables([...($this->getVariables() ?? []), ...[
            '@name'       => $name,
            '@arguments'  => $arguments,
            '@parameters' => $parameters,
            '@input'      => $input,
            '@output'     => $output,
        ]]);
        
        if (empty($comparison)) {
            return $output;
        }

        // the comparison is copied here because the operator can be a word and when
        // the placeholders are injected into the $comparison array it will be nullified
        // the comparison is cached here to make use of it later as fallbacks
        /** @var mixed[] $currentComparison */
        $currentComparison = $comparison;

        /** @var mixed[] $defaultComparison */
        $defaultComparison = static::DEFAULT['@comparison'];

        $comparison = Utils::injectInArray($comparison, [
            '@rule'       => $rule,
            '@name'       => $name,
            '@arguments'  => $arguments,
            '@callback'   => $callback,
            '@parameters' => $parameters,
            '@input'      => $input,
            '@output'     => $output,
        ]);

        /** @var callable $getFromComparison */
        $getFromComparison = fn ($index) => array_key_exists($index, $comparison)
            // the check is done only for the operator, null is valid in other positions
            ? ($index !== 1 ? $comparison[$index] : $comparison[$index] ?? $currentComparison[$index])
            : $defaultComparison[$index] ?? null;

        $actual   = $getFromComparison(0);
        $operator = $getFromComparison(1);
        $expected = $getFromComparison(2);
        
        /** @var callable $assert */
        $assert = LogicComparator::$operators[$operator] ?? LogicComparator::$operators['&&'];
        $result = $assert($actual, $expected);

        return $result;
    }

    /**
     * Creates an error message for the rule using the currently available variables or the passed ones.
     *
     * @param string|null $message [optional] Override for current rule message.
     * @param array<mixed>|null $variables [optional] Overrides for current rule variables (will be merged with current rule variables).
     *
     * @return string The message with the variables injected.
     */
    public function createErrorMessage(?string $message = null, ?array $variables = null): string
    {
        /** @var string $messageFallback */
        $messageFallback = $this->definition['@message'] ?? static::DEFAULT['@message'];

        /** @var mixed[] $variablesFallback */
        $variablesFallback = $this->definition['@variables'] ?? static::DEFAULT['@variables'];

        $message   = ($message ?? '') ?: $messageFallback;
        $variables = ($variables ?? []) + $variablesFallback;

        // $message = (static::$translator)($message, $this->getName());

        $result = Utils::injectInString((string)$message, (array)$variables);

        // ${@label} will be wrapped with double quotes when injected, this removes the ""
        $result = strtr($result, [
            sprintf('"%s"', ($label = $variables['@label'] ?? uniqid())) => $label,
        ]);

        return $result;
    }
}