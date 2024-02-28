<?php

namespace AjdVal\Expression\Parsers;

use Ajd\Expression\Parser\Parser;
use Ajd\Expression\Lexer\Token;
use Ajd\Expression\Lexer\TokenStream;
use Ajd\Expression\Lexer\SyntaxError;
use AjdVal\Expression\Nodes as ValNodes;
use Ajd\Expression\Nodes;

class ExpressionParserValidation extends Parser
{
    public function __construct(array $functions, protected array $rules = [])
    {
        parent::__construct($functions);
    }

    public function parseExpression(int $precedence = 0): Nodes\Node
    {
        $expr = $this->getPrimary();
        $token = $this->stream->current;

        while (
            $token->test(Token::OPERATOR_TYPE) 
            && isset($this->parserConfig->binaryOperators[$token->value]) 
            && $this->parserConfig->binaryOperators[$token->value]['precedence'] >= $precedence
        ) {

            $op = $this->parserConfig->binaryOperators[$token->value];
            $this->stream->next();

            $expr1 = $this->parseExpression(
                self::OPERATOR_LEFT === $op['associativity'] 
                    ? $op['precedence'] + 1 
                    : $op['precedence']
            );
            
            $expr = new ValNodes\ValidationBinaryNode($token->value, $expr, $expr1);

            $token = $this->stream->current;
        }

        if (0 === $precedence) {
            return $this->parseConditionalExpression($expr);
        }

        return $expr;
    }

    protected function getPrimary(): Nodes\Node
    {
        $token = $this->stream->current;

        if (
            $token->test(Token::OPERATOR_TYPE) 
            && isset($this->parserConfig->unaryOperators[$token->value])

        ) {
            $operator = $this->parserConfig->unaryOperators[$token->value];
            $this->stream->next();
            $expr = $this->parseExpression($operator['precedence']);

            return $this->parsePostfixExpression(new ValNodes\ValidationUnaryNode($token->value, $expr));
        }

        if ($token->test(Token::PUNCTUATION_TYPE, '(')) {
            $this->stream->next();
            $expr = $this->parseExpression();
            $this->stream->expect(Token::PUNCTUATION_TYPE, ')', 'An opened parenthesis is not properly closed');

            return $this->parsePostfixExpression($expr);
        }

        return $this->parsePrimaryExpression();
    }

    public function parsePrimaryExpression(): Nodes\Node
    {
        $token = $this->stream->current;

        switch ($token->type) {
            case Token::NAME_TYPE:

                $this->stream->next();

                switch ($token->value) {
                    case 'true':
                    case 'TRUE':
                        return new Nodes\ConstantNode(true);

                    case 'false':
                    case 'FALSE':
                        return new Nodes\ConstantNode(false);

                    case 'null':
                    case 'NULL':
                        return new Nodes\ConstantNode(null);

                    default:
                        if ('(' === $this->stream->current->value) {
                            if (isset($this->rules[$token->value])) {
                                if ($token->value !== 'when') {
                                    $node = new ValNodes\ValidationNode($token->value, $this->parseArguments());        
                                }

                                if ($token->value === 'when') {
                                    $node = new ValNodes\WhenFunctionNode($token->value, $this->parseArguments());        
                                }
                                
                            } else {
                                if (false === isset($this->functions[$token->value])) {

                                    throw new SyntaxError(
                                        sprintf('The function "%s" does not exist.', $token->value), 
                                        $token->cursor, 
                                        $this->stream->getExpression(), $token->value, 
                                        array_keys($this->functions)
                                    );
                                }

                                if ($token->value === 'when') {
                                    $node = new ValNodes\WhenFunctionNode($token->value, $this->parseArguments());
                                } else {
                                    $node = new Nodes\FunctionNode($token->value, $this->parseArguments());             
                                }
                            }
                        } else {

                            if (!$this->lint || \is_array($this->names)) {
                                if (!\in_array($token->value, $this->names, true)) {
                                    throw new SyntaxError(
                                        sprintf('Variable "%s" is not valid.', $token->value), 
                                        $token->cursor, 
                                        $this->stream->getExpression(), 
                                        $token->value, 
                                        $this->names
                                    );
                                }

                                // is the name used in the compiled code different
                                // from the name used in the expression?
                                if (\is_int($name = array_search($token->value, $this->names))) {
                                    $name = $token->value;
                                }
                            } else {
                                $name = $token->value;
                            }

                            $node = new Nodes\NameNode($name);
                        }
                }
                break;

            case Token::NUMBER_TYPE:
            case Token::STRING_TYPE:
                $this->stream->next();

                return new Nodes\ConstantNode($token->value);

             case static::TOKEN_REPLACEMENT_TYPE:
                $this->stream->next();

                return $this->replacementNodes[$token->value];


            default:
                if ($token->test(Token::PUNCTUATION_TYPE, '[')) {
                    $node = $this->parseArrayExpression();
                } elseif ($token->test(Token::PUNCTUATION_TYPE, '{')) {
                    $node = $this->parseHashExpression();
                } else {
                    throw new SyntaxError(
                        sprintf(
                            'Unexpected token "%s" of value "%s".', 
                            $token->type, 
                            $token->value
                        ), 
                        $token->cursor, 
                        $this->stream->getExpression()
                    );
                }
        }

        return $this->parsePostfixExpression($node);
    }
}