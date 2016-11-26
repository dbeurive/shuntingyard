<?php

use dbeurive\Shuntingyard\ShuntingYard;
use dbeurive\Lexer\Token;

class ShuntingYardTest extends \PHPUnit_Framework_TestCase
{
    const T_STRING          = 'STRING';
    const T_VARIABLE        = 'VARIABLE';
    const T_FUNCTION        = 'FUNCTION';
    const T_NUMERIC         = 'NUMERIC';
    const T_PARAM_SEPARATOR = 'PARAM_SEPARATOR';
    const T_OPEN_BRACKET    = 'OPEN_BRACKET';
    const T_CLOSE_BRACKET   = 'CLOSE_BRACKET';
    const T_OPERATOR        = 'OPERATOR';
    const T_SPACE           = 'SPACE';

    /** @var ShuntingYard|null */
    private $__sy = null;

    public function setUp() {
        $tokens = array(
            array('/"(?:[^"\\\\]|\\\\["\\\\])+"/',                self::T_STRING),
            array('/V\\d+/',                                      self::T_VARIABLE),
            array('/[a-z_]+[0-9]*/',                              self::T_FUNCTION),
            array('/\\d+/',                                       self::T_NUMERIC),
            array('/,/',                                          self::T_PARAM_SEPARATOR),
            array('/\\(/',                                        self::T_OPEN_BRACKET),
            array('/\\)/',                                        self::T_CLOSE_BRACKET),
            array('/(<>|~|%|\\+|\\-|\\*|\\/|\\^|>=|<=|>|<|=|&)/', self::T_OPERATOR),
            array('/\\s+/',                                       self::T_SPACE, function(array $m) { return null; })
        );

        $precedences = array(
            '%'     => 4,
            '~'     => 4,
            '^'     => 4,
            '&'     => 3,
            '*'     => 3,
            '/'     => 3,
            '+'     => 2,
            '-'     => 2,
            '>'     => 1,
            '<'     => 1,
            '>='    => 1,
            '<='    => 1,
            '='     => 1,
            '<>'    => 1
        );

        $associativities = array(
            '~'     => ShuntingYard::ASSOC_RIGHT,
            '%'     => ShuntingYard::ASSOC_RIGHT,
            '^'     => ShuntingYard::ASSOC_RIGHT,
            '&'     => ShuntingYard::ASSOC_LEFT,
            '*'     => ShuntingYard::ASSOC_LEFT,
            '/'     => ShuntingYard::ASSOC_LEFT,
            '+'     => ShuntingYard::ASSOC_LEFT,
            '-'     => ShuntingYard::ASSOC_LEFT,
            '>'     => ShuntingYard::ASSOC_LEFT,
            '<'     => ShuntingYard::ASSOC_LEFT,
            '>='    => ShuntingYard::ASSOC_LEFT,
            '<='    => ShuntingYard::ASSOC_LEFT,
            '='     => ShuntingYard::ASSOC_LEFT,
            '<>'    => ShuntingYard::ASSOC_LEFT
        );

        $this->__sy = new ShuntingYard(
            $tokens,
            $precedences,
            $associativities,
            array(self::T_VARIABLE, self::T_STRING, self::T_NUMERIC),
            array(self::T_FUNCTION),
            array(self::T_OPERATOR),
            self::T_PARAM_SEPARATOR,
            self::T_OPEN_BRACKET,
            self::T_CLOSE_BRACKET
        );
    }
    
    public function testConvertSuccess() {

        $error = null;

        $text = 'V1 + V2';
        $expected = array(
            new Token('V1', self::T_VARIABLE),
            new Token('V2', self::T_VARIABLE),
            new Token('+',  self::T_OPERATOR)
        );
        $tokens = $this->__sy->convert($text, $error);
        $this->assertEquals($expected, $tokens);
        
        $text = '"azerty" / V1 + V2 * sin(10)';
        $expected = array(
            new Token('"azerty"', self::T_STRING),
            new Token('V1',       self::T_VARIABLE),
            new Token('/',        self::T_OPERATOR),
            new Token('V2',       self::T_VARIABLE),
            new Token('10',       self::T_NUMERIC),
            new Token('sin',      self::T_FUNCTION),
            new Token('*',        self::T_OPERATOR),
            new Token('+',        self::T_OPERATOR)
        );
        $tokens = $this->__sy->convert($text, $error);
        $this->assertEquals($expected, $tokens);
    }

    private function __dumpToken(array $inTokens) {
        $max = 0;

        /** @var Token $_token */
        foreach ($inTokens as $_token) {
            $max = strlen($_token->type) > $max ? strlen($_token->type) : $max;
        }

        /** @var Token $_token */
        foreach ($inTokens as $_token) {
            printf("\t%${max}s %s\n", $_token->type, $_token->value);
        }
    }
}