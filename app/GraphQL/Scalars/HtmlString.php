<?php declare(strict_types = 1);

namespace App\GraphQL\Scalars;

use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\StringType;
use Stevebauman\Purify\Facades\Purify;

class HtmlString extends StringType {
    public function serialize(mixed $value): string {
        return $this->clean(parent::serialize($value));
    }

    public function parseValue(mixed $value): string {
        return $this->clean(parent::parseValue($value));
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): string {
        return $this->clean(parent::parseLiteral($valueNode, $variables));
    }

    protected function clean(string $string): string {
        return Purify::clean($string);
    }
}
