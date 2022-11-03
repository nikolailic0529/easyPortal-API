<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Export\Exceptions\SelectorSyntaxError;
use App\Http\Controllers\Export\Exceptions\SelectorUnknown;
use App\Http\Controllers\Export\Selectors\Asterisk;
use App\Http\Controllers\Export\Selectors\Concat;
use App\Http\Controllers\Export\Selectors\Group;
use App\Http\Controllers\Export\Selectors\LogicalOr;
use App\Http\Controllers\Export\Selectors\Property;
use App\Http\Controllers\Export\Selectors\Root;

use function array_slice;
use function count;
use function explode;
use function implode;
use function preg_match;
use function preg_split;
use function str_contains;
use function trim;

use const PREG_SPLIT_DELIM_CAPTURE;

class SelectorFactory {
    /**
     * @param array<int<0, max>, string> $selectors
     */
    public static function make(array $selectors): Root {
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
     * @param int<0, max>          $index
     * @param array<string, Group> $groups
     */
    protected static function parseSelector(string $selector, int $index = 0, array &$groups = []): ?Selector {
        $instance = null;
        $selector = trim($selector);

        if (!$selector) {
            return $instance;
        }

        if (str_contains($selector, '(') || str_contains($selector, ')')) {
            if (preg_match('/^(?<function>[\w]+)\((?<arguments>.+)?\)$/', $selector, $matches)) {
                $function  = $matches['function'];
                $arguments = static::parseArguments($matches['arguments'] ?? '');

                switch ($function) {
                    case Concat::getName():
                        $instance = new Concat($arguments, $index);
                        break;
                    case LogicalOr::getName():
                        $instance = new LogicalOr($arguments, $index);
                        break;
                    default:
                        throw new SelectorUnknown($function);
                }
            } else {
                throw new SelectorSyntaxError();
            }
        } else {
            $instance = self::parseProperty($selector, $index, $groups);
        }

        return $instance;
    }

    /**
     * @param int<0, max>          $index
     * @param array<string, Group> $groups
     */
    protected static function parseProperty(string $selector, int $index = 0, array &$groups = []): ?Selector {
        $separator = '.';
        $instance  = null;
        $parts     = explode($separator, $selector);
        $count     = count($parts);
        $group     = null;
        $path      = '';

        foreach ($parts as $i => $part) {
            if ($part === '*') {
                if (isset($parts[$i + 1])) {
                    $property = implode($separator, array_slice($parts, $i + 1));
                    $property = static::parseProperty($property);

                    if ($property) {
                        $asterisk = new Asterisk($property, $index);

                        if ($group) {
                            $group->add($asterisk);
                        } else {
                            $instance = $asterisk;
                        }
                    }
                }

                break;
            } elseif ($i !== $count - 1) {
                $path           .= ".{$part}";
                $groups[$path] ??= new Group($part);
                $instance      ??= $groups[$path];

                if ($group) {
                    $group->add($groups[$path]);
                }

                $group = $groups[$path];
            } elseif ($group) {
                $group->add(new Property($part, $index));
            } else {
                $instance = new Property($part, $index);
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
