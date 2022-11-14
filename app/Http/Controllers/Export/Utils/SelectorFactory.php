<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Utils;

use App\Http\Controllers\Export\Exceptions\SelectorAsteriskPropertyUnknown;
use App\Http\Controllers\Export\Exceptions\SelectorFunctionUnknown;
use App\Http\Controllers\Export\Exceptions\SelectorSyntaxError;
use App\Http\Controllers\Export\Selector;
use App\Http\Controllers\Export\Selectors\Asterisk;
use App\Http\Controllers\Export\Selectors\Concat;
use App\Http\Controllers\Export\Selectors\Date;
use App\Http\Controllers\Export\Selectors\DateTime;
use App\Http\Controllers\Export\Selectors\Decimal;
use App\Http\Controllers\Export\Selectors\Filesize;
use App\Http\Controllers\Export\Selectors\Group;
use App\Http\Controllers\Export\Selectors\Integer;
use App\Http\Controllers\Export\Selectors\LogicalOr;
use App\Http\Controllers\Export\Selectors\Property;
use App\Http\Controllers\Export\Selectors\Root;
use App\Http\Controllers\Export\Selectors\Time;
use App\Services\I18n\Formatter;

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
    public static function make(Formatter $formatter, array $selectors): Root {
        $root   = new Root();
        $groups = [];

        foreach ($selectors as $index => $selector) {
            $selector = static::parseSelector($formatter, $selector, $index, $groups);

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
    protected static function parseSelector(
        Formatter $formatter,
        string $selector,
        int $index = 0,
        array &$groups = [],
    ): ?Selector {
        $instance = null;
        $selector = trim($selector);

        if (!$selector) {
            return $instance;
        }

        if (str_contains($selector, '(') || str_contains($selector, ')')) {
            if (preg_match('/^(?<function>[\w]+)\((?<arguments>.+)?\)$/', $selector, $matches)) {
                $function  = $matches['function'];
                $arguments = static::parseArguments($formatter, $matches['arguments'] ?? '');
                $instance  = match ($function) {
                    Concat::getName()    => new Concat($arguments, $index),
                    LogicalOr::getName() => new LogicalOr($arguments, $index),
                    Integer::getName()   => new Integer($formatter, $arguments, $index),
                    Decimal::getName()   => new Decimal($formatter, $arguments, $index),
                    Date::getName()      => new Date($formatter, $arguments, $index),
                    DateTime::getName()  => new DateTime($formatter, $arguments, $index),
                    Time::getName()      => new Time($formatter, $arguments, $index),
                    Filesize::getName()  => new Filesize($formatter, $arguments, $index),
                    default              => throw new SelectorFunctionUnknown($function),
                };
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
        $selector  = trim($selector);
        $parts     = explode($separator, $selector);
        $count     = count($parts);
        $group     = null;
        $path      = '';

        if (!$selector) {
            return $instance;
        }

        foreach ($parts as $i => $part) {
            if ($part === '*') {
                $property = implode($separator, array_slice($parts, $i + 1));
                $property = static::parseProperty($property);

                if ($property) {
                    $asterisk = new Asterisk($property, $index);

                    if ($group) {
                        $group->add($asterisk);
                    } else {
                        $instance = $asterisk;
                    }
                } else {
                    throw new SelectorAsteriskPropertyUnknown();
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
    protected static function parseArguments(Formatter $formatter, string $arguments): array {
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
                        $selector = static::parseSelector($formatter, $buffer);
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
        $selector = static::parseSelector($formatter, $buffer);

        if ($selector) {
            $args[] = $selector;
        }

        // Return
        return $args;
    }
}
