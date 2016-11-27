<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
use dbeurive\Shuntingyard\ShuntingYard;

define('TYPE_STRING',          'STRING');
define('TYPE_VARIABLE',        'VARIABLE');
define('TYPE_FUNCTION',        'FUNCTION');
define('TYPE_NUMERIC',         'NUMERIC');
define('TYPE_PARAM_SEPARATOR', 'PARAM_SEPARATOR');
define('TYPE_OPEN_BRACKET',    'OPEN_BRACKET');
define('TYPE_CLOSE_BRACKET',   'CLOSE_BRACKET');
define('TYPE_OPERATOR',        'OPERATOR');
define('TYPE_SPACE',           'SPACE');

$tokens = array(
    array('/"(?:[^"\\\\]|\\\\["\\\\])+"/',                TYPE_STRING),
    array('/V\\d+/',                                      TYPE_VARIABLE),
    array('/[a-z_]+[0-9]*/',                              TYPE_FUNCTION),
    array('/\\d+/',                                       TYPE_NUMERIC),
    array('/,/',                                          TYPE_PARAM_SEPARATOR),
    array('/\\(/',                                        TYPE_OPEN_BRACKET),
    array('/\\)/',                                        TYPE_CLOSE_BRACKET),
    array('/(<>|~|%|\\+|\\-|\\*|\\/|\\^|>=|<=|>|<|=|&)/', TYPE_OPERATOR),
    array('/\\s+/',                                       TYPE_SPACE, function(array $m) { return null; })
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

$sy = new ShuntingYard(
    $tokens,
    $precedences,
    $associativities,
    array(TYPE_VARIABLE, TYPE_STRING, TYPE_NUMERIC),
    array(TYPE_FUNCTION),
    array(TYPE_OPERATOR),
    TYPE_PARAM_SEPARATOR,
    TYPE_OPEN_BRACKET,
    TYPE_CLOSE_BRACKET
);

$text = '"azerty" / V1 + V2 * sin(10)';
$tokens = $sy->convert($text, $error);
print "$text:\n\n" . $sy->dumpRpn($tokens) . "\n\n";