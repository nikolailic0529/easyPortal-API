<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Export\Selectors\Asterisk;
use App\Http\Controllers\Export\Selectors\Concat;
use App\Http\Controllers\Export\Selectors\Group;
use App\Http\Controllers\Export\Selectors\LogicalOr;
use App\Http\Controllers\Export\Selectors\Root;
use App\Http\Controllers\Export\Selectors\Value;

use function array_slice;
use function count;
use function explode;
use function implode;
use function preg_match;
use function preg_split;
use function trim;

use const PREG_SPLIT_DELIM_CAPTURE;

class SelectorFactory {
    /**
     * @param array<int, string> $selectors
     */
    public static function make(array $selectors): Selector {
        $root   = new Root();
        $groups = [];

        foreach ($selectors as $index => $selector) {
            $selector = static::parseSelector($selector, $index, $groups);

            if ($selector) {
                $root->add($selector);
            }
        }

        return $root;
    }

    /**
     * @param array<string, Group> $groups
     */
    protected static function parseSelector(string $selector, int $index = 0, array &$groups = []): ?Selector {
        $instance = null;
        $selector = trim($selector);

        if (!$selector) {
            return $instance;
        }

        if (preg_match('/^(?<function>[\w]+)\((?<arguments>.+)\)$/', $selector, $matches)) {
            $function  = $matches['function'];
            $arguments = static::parseArguments($matches['arguments']);

            if ($arguments) {
                switch ($function) {
                    case 'concat':
                        $instance = new Concat($arguments, $index);
                        break;
                    case 'or':
                        $instance = new LogicalOr($arguments, $index);
                        break;
                    default:
                        throw new HeadersUnknownFunction($function);
                }
            }
        } else {
            $separator = '.';
            $parts     = explode($separator, $selector);
            $count     = count($parts);
            $group     = null;
            $path      = '';

            foreach ($parts as $i => $part) {
                if ($part === '*') {
                    if (isset($parts[$i + 1])) {
                        $property = implode($separator, array_slice($parts, $i + 1));
                        $asterisk = new Asterisk($property, $index);

                        if ($group) {
                            $group->add($asterisk);
                        } else {
                            $instance = $asterisk;
                        }
                    }

                    break;
                } elseif ($i !== $count - 1) {
                    $path           .= ".{$part}";
                    $groups[$path] ??= new Group($part);
                    $instance      ??= $groups[$path];
                    $group           = $groups[$path];
                } elseif ($group) {
                    $group->add(new Value($part, $index));
                } else {
                    $instance = new Value($part, $index);
                }
            }
        }

        return $instance;
    }

    /**
     * @return array<int, Selector>
     */
    protected static function parseArguments(string $arguments): array {
        $args   = [];
        $level  = 0;
        $buffer = '';
        $tokens = preg_split('/([(),])/', $arguments, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        foreach ($tokens as $token) {
            switch ($token) {
                case '(':
                    $level++;
                    break;
                case ')':
                    $level--;
                    break;
                case ',':
                    if ($level === 0) {
                        $selector = static::parseSelector($buffer);
                        $buffer   = '';
                        $token    = '';

                        if ($selector) {
                            $args[] = $selector;
                        }
                    }
                    break;
                default:
                    // empty
                    break;
            }

            $buffer .= $token;
        }

        // Rest
        $selector = static::parseSelector($buffer);

        if ($selector) {
            $args[] = $selector;
        }

        // Return
        return $args;
    }
}
