<?php

//    Copyright (c) 2016 Denis BEURIVE
//
//    This work is licensed under the Creative Commons Attribution 3.0
//    Unported License.
//
//    A summary of the license is given below.
//
//    --------------------------------------------------------------------
//
//    You are free:
//
//    * to Share - to copy, distribute and transmit the work
//    * to Remix - to adapt the work
//
//    Under the following conditions:
//
//    Attribution. You must attribute the work in the manner specified by
//    the author or licensor (but not in any way that suggests that they
//    endorse you or your use of the work).
//
//        * For any reuse or distribution, you must make clear to others
//          the license terms of this work.
//
//        * Any of the above conditions can be waived if you get
//          permission from the copyright holder.
//
//        * Nothing in this license impairs or restricts the author's moral
//          rights.
//
//    Your fair dealing and other rights are in no way affected by the
//    above.

/**
 * This file implements the shunting yard algorithm.
 */

namespace dbeurive\Shuntingyard;
use dbeurive\Lexer\Lexer;
use dbeurive\Lexer\Token;

/**
 * Class ShuntingYard
 *
 * This class implements the shunting yard algorithm.
 *
 * @package dbeurive\Shuntingyard
 */
class ShuntingYard
{
    const ASSOC_RIGHT = 'right';
    const ASSOC_LEFT = 'left';
    const ERROR_KEY_MESSAGE = 'message';
    const ERROR_KEY_FORMULA = 'formula';
    const DEBUG = false;

    /** @var array|null List of tokens. */
    private $__tokens = null;
    /** @var array|null Precedence specifications. */
    private $__precedences = null;
    /** @var array|null Associativity specifications. */
    private $__associativity = null;
    /** @var array|null Types that represents values. */
    private $__valueTypes = null;
    /** @var array|null Types that represents a function. */
    private $__functionTypes = null;
    /** @var string|null Type that represents the parameters' separator (within a function call). */
    private $__paramSeparatorType = null;
    /** @var array|null Types that represents an operator. */
    private $__operatorTypes = null;
    /** @var string|null Type that represents the opening of a list of parameters. */
    private $__openListDelimiter = null;
    /** @var string|null Type that represents the closing of a list of parameters. */
    private $__closeListDelimiter = null;
    /** @var array Operators stack. */
    private $__operatorStack = array();
    /** @var array RPN representation of the infix expression being processed. */
    private $__outputQueue = array();

    /**
     * ShuntingYard constructor.
     * @param array $inTokens List of tokens.
     * @param array $inPrecedences Precedence specifications.
     * @param array $inAssociativity Associativity specifications.
     * @param array $inInValueTypes Types (of tokens) that represents values.
     * @param array $inFunctionTypes Type that represents a function.
     * @param array $inOperatorTypes Type that represents an operator.
     * @param string $inParamSeparatorType Type that represents the parameters' separator.
     * @param string $inOpenListDelimiterType Type that represents the opening of a list of parameter.
     * @param string $inCloseListDelimiterType Type that represents the closing of a list of parameter.
     */
    public function __construct(array $inTokens,
                                array $inPrecedences,
                                array $inAssociativity,
                                array $inInValueTypes,
                                array $inFunctionTypes,
                                array $inOperatorTypes,
                                $inParamSeparatorType,
                                $inOpenListDelimiterType,
                                $inCloseListDelimiterType)
    {
        $this->__tokens = $inTokens;
        $this->__precedences = $inPrecedences;
        $this->__associativity = $inAssociativity;
        $this->__valueTypes = $inInValueTypes;
        $this->__functionTypes = $inFunctionTypes;
        $this->__paramSeparatorType = $inParamSeparatorType;
        $this->__openListDelimiter = $inOpenListDelimiterType;
        $this->__closeListDelimiter = $inCloseListDelimiterType;
        $this->__operatorTypes = $inOperatorTypes;
    }

    /**
     * Reset the parser's internal states.
     */
    private function __reset() {
        $this->__operatorStack = array();
        $this->__outputQueue = array();
    }

    private function __pushToOperatorStack(Token $inToken) {
        $this->__debug("Push $inToken->value into the operator stack.");
        $this->__operatorStack[] = $inToken;
        return $this;
    }

    /**
     * Pop the token on top of the operator's stack.
     * @return Token|null If the stack is empty, then the method returns the value null.
     *         Otherwise, it returns the token on top of the operator's stack.
     */
    private function __popOffOperatorStack() {
        /** @var Token $e */
        $e = array_pop($this->__operatorStack);
        $this->__debug("Pop " . (is_null($e) ? 'NULL' : $e->value) . " from the operator stack.");
        return $e;
    }

    /**
     * Return the token on top of the operator's stack.
     * @return Token|null If the stack is empty, then the method returns the value null.
     *         Otherwise, it returns the token on top of the operator's stack.
     */
    private function __peekTopOfOperatorStack() {
        $count = count($this->__operatorStack);
        if (0 == $count) {
            return null;
        }
        /** @var Token $e */
        $e = $this->__operatorStack[$count - 1];
        $this->__debug("Peek $e->value from the operator stack.");
        return $e;
    }

    /**
     * Push a given token into the output queue.
     * @param Token $inToken Token to push.
     */
    private function __pushToOutputQueue(Token $inToken) {
        $this->__debug("Push $inToken->value to the output queue.");
        array_unshift($this->__outputQueue, $inToken);
    }

    /**
     * Convert Lex token list into the corresponding RPN representation.
     * @param string $tokens Lex token list to convert.
     * @param array $outError Reference to an associative array used to store information about an error.
     *        Array's keys are:
     *        - ShuntingYard::ERROR_KEY_MESSAGE: the error message.
     *        - ShuntingYard::ERROR_KEY_FORMULA: the formula that caused the error.
     * @return array|false Upon successful completion, the method returns an array that contains the RPN representation of the given infix expression.
     *         Otherwise, the method returns the value false.
     */
    public function convertFromTokens($tokens, &$outError)
    {
        return $this->_convert($tokens, 'Tokens given', $outError);
    }

    /**
     * Parse a given infix expression and convert it into the corresponding RPN representation.
     * @param string $inInfixExpression Infix expression to parse.
     * @param array $outError Reference to an associative array used to store information about an error.
     *        Array's keys are:
     *        - ShuntingYard::ERROR_KEY_MESSAGE: the error message.
     *        - ShuntingYard::ERROR_KEY_FORMULA: the formula that caused the error.
     * @return array|false Upon successful completion, the method returns an array that contains the RPN representation of the given infix expression.
     *         Otherwise, the method returns the value false.
     */
    public function convert($inInfixExpression, &$outError) {
        $lexer = new Lexer($this->__tokens);
        $tokens = $lexer->lex($inInfixExpression);
        return $this->_convert($tokens, $inInfixExpression, $outError);
    }

    public function _convert($tokens, $inInfixExpression, &$outError) {
        $outError = null;
        $this->__reset();
        /**
        * @var int $_index
        * @var Token $_token
        */
        foreach ($tokens as $_index => $_token) {

            if (in_array($_token->type, $this->__valueTypes)) {
                // The token represents a value.
                $this->__pushToOutputQueue($_token);
                continue; // Continue the foreach loop.
            }

            if (in_array($_token->type, $this->__functionTypes)) {
                // The token represents a function.
                $this->__pushToOperatorStack($_token);
                continue; // Continue the foreach loop.
            }

            if ($_token->type == $this->__paramSeparatorType) {
                while(true) {
                    $_operator = $this->__peekTopOfOperatorStack();
                    if (is_null($_operator)) {
                        $outError = array(
                            self::ERROR_KEY_MESSAGE => "Could not find closing token in expression.",
                            self::ERROR_KEY_FORMULA => $inInfixExpression);
                        return false;
                    }

                    if ($_operator->type == $this->__openListDelimiter) {
                        break;
                    }

                    $_operator = $this->__popOffOperatorStack();
                    $this->__pushToOutputQueue($_operator);
                }
                continue; // Continue the foreach loop.
            }

            if (in_array($_token->type, $this->__operatorTypes)) {
                $_operatorPrecedence = $this->__precedences[$_token->value];
                $_operatorAssociativity = $this->__associativity[$_token->value];

                while (true) {

                    $_stackElement = $this->__peekTopOfOperatorStack();
                    if (is_null($_stackElement)) {
                        break;
                    }

                    if (! in_array($_stackElement->type, $this->__operatorTypes)) {
                        break;
                    }

                    $_stackElementPrecedence    = $this->__precedences[$_stackElement->value];

                    if (
                            (
                                (self::ASSOC_LEFT == $_operatorAssociativity)
                                &&
                                ($_operatorPrecedence <= $_stackElementPrecedence)
                            )
                            ||
                            (
                                (self::ASSOC_RIGHT == $_operatorAssociativity)
                                &&
                                ($_operatorPrecedence < $_stackElementPrecedence)
                            )
                    ) {
                        $__operator = $this->__popOffOperatorStack();
                        $this->__pushToOutputQueue($__operator);
                    } else {
                        break;
                    }
                }

                $this->__pushToOperatorStack($_token);
                continue; // Continue the foreach loop.
            }


            if ($_token->type == $this->__openListDelimiter) {
                $this->__pushToOperatorStack($_token);
                continue; // Continue the foreach loop.
            }

            if ($_token->type == $this->__closeListDelimiter) {

                while (true) {
                    $_operator = $this->__popOffOperatorStack();
                    if (is_null($_operator)) {
                        $outError = array(
                            self::ERROR_KEY_MESSAGE => "Could not find closing token in expression",
                            self::ERROR_KEY_FORMULA => $inInfixExpression);
                        return false;
                    }

                    if ($_operator->type == $this->__openListDelimiter) {
                        break;
                    }
                    $this->__pushToOutputQueue($_operator);
                }

                $_operator = $this->__peekTopOfOperatorStack();

                if (! is_null($_operator)) {
                    if ($this->__functionTypes == $_operator->type) {
                        $this->__pushToOutputQueue($this->__popOffOperatorStack());
                    }
                    continue; // Continue the foreach loop.
                }
            }
        }

        while(true) {
            $_operator = $this->__popOffOperatorStack();
            if (is_null($_operator)) {
                break;
            }
            if (in_array($_operator->type, array($this->__openListDelimiter, $this->__closeListDelimiter))) {
                $outError = array(
                    self::ERROR_KEY_MESSAGE => "Something is messed up with lists of parameters delimiters in expression",
                    self::ERROR_KEY_FORMULA => $inInfixExpression);
                return false;
            }
            $this->__pushToOutputQueue($_operator);
        }

        return array_reverse($this->__outputQueue);
    }

    /**
     * Return the RPN representation of the lastly parsed infix expression.
     * @return array The method returns an array that contains the RPN representation of the lastly parsed infix expression.
     */
    public function getRpn() {
        return array_reverse($this->__outputQueue);
    }

    /**
     * Return a text that represent a given RPN representation.
     * @param array $inRpnRepresentation the RPN representation.
     * @return string The method returns a string that represents the given RPN representation.
     */
    public function dumpRpn(array $inRpnRepresentation) {
        $m = 0;

        /** @var Token $_token */
        foreach ($inRpnRepresentation as $_token) {
            $m = $m > strlen($_token->type) ? $m : strlen($_token->type);
        }

        $result = array();
        foreach ($inRpnRepresentation as $_token) {
            $result[] = sprintf("%" . $m . 's %s', $_token->type, $_token->value);
        }
        return implode(PHP_EOL, $result);
    }

    /**
     * This method is used to write debug information.
     * @param string $inText Message to write.
     */
    private function __debug($inText) {
        if (self::DEBUG) {
            print "$inText\n";
        }
    }
}
