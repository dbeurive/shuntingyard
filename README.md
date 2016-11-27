# Introduction

This repository contains an implementation of the [Shunting Yard algorithm](https://en.wikipedia.org/wiki/Shunting-yard_algorithm).

# Installation

From the command line:

	composer require dbeurive\shuntingyard

If you want to include this package to your project, then edit your file `composer.json` and add the following entry:

	"require": {
    	"dbeurive/shuntingyard": "*"
	}

# Synopsis

```php

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
```

The result:

	"azerty" / V1 + V2 * sin(10):
	
	  STRING "azerty"
	VARIABLE V1
	OPERATOR /
	VARIABLE V2
	 NUMERIC 10
	FUNCTION sin
	OPERATOR *
	OPERATOR +

# Configuration

The algorithm is configured by:

* The list of tokens (see the [documentation for the class dbeurive\Lexer\Lexer](https://github.com/dbeurive/lexer/blob/master/README.md)).
* The operators' precedencies.
* The operators' associativities.
* The list of tokens' types that represents variables.
* The list of tokens' types that represents functions.
* The list of tokens' types that represents operators.
* The token's type that represents the parameters separator.
* The token's type that represents the start of a list of parameters (typically "(").
* The token's type that represents the end of a list of parameters (typically ")").

> **WARNING**
>
> Make sure to double all characters "`\`" within the regular expressions that define the tokens.
> That is: `'/\s/'` becomes `'/\\s/'.`


The synopsis should be clear enough. You can also consult the [example](examples/example.php).


