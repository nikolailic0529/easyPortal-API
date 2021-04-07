<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Settings\Attributes\CronJob as CronJobAttribute;
use App\Services\Settings\Attributes\Group;
use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Job as JobAttribute;
use App\Services\Settings\Attributes\Secret as SecretAttribute;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Attributes\Type as TypeAttribute;
use App\Services\Settings\Types\BooleanType;
use App\Services\Settings\Types\FloatType;
use App\Services\Settings\Types\IntType;
use App\Services\Settings\Types\StringType;
use App\Services\Settings\Types\Type;
use Illuminate\Contracts\Config\Repository;
use InvalidArgumentException;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionAttribute;
use ReflectionClassConstant;

use function __;
use function gettype;
use function implode;
use function is_array;
use function reset;
use function sprintf;
use function trim;

class Setting {
    protected SettingAttribute $definition;

    public function __construct(
        protected Repository $config,
        protected ReflectionClassConstant $constant,
        protected bool $readonly = false,
    ) {
        $attributes = [
            CronJobAttribute::class,
            JobAttribute::class,
            SettingAttribute::class,
        ];
        $attribute  = $this->getAttribute(...$attributes)?->newInstance();

        if ($attribute instanceof SettingAttribute) {
            $this->definition = $attribute;
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$constant` must have one of the following attributes `%s`.',
                implode('`, `', $attributes),
            ));
        }
    }

    public function getName(): string {
        return $this->constant->getName();
    }

    public function getPath(): string {
        return $this->definition->getName();
    }

    public function getType(): Type {
        $type     = null;
        $declared = $this->getAttribute(TypeAttribute::class)?->newInstance();

        if ($declared instanceof TypeAttribute) {
            $type = $declared->getType();
        } else {
            $value   = $this->constant->getValue();
            $default = gettype($value);

            switch ($default) {
                case 'boolean':
                    $type = new BooleanType();
                    break;
                case 'integer':
                    $type = new IntType();
                    break;
                case 'double':
                    $type = new FloatType();
                    break;
                case 'string':
                    $type = new StringType();
                    break;
                default:
                    throw new InvalidArgumentException(sprintf(
                        'Type `%s` not supported.',
                        $default,
                    ));
                    break;
            }
        }

        return $type;
    }

    public function getTypeName(): string {
        return $this->getType()->getName();
    }

    public function getValue(): mixed {
        return $this->config->get($this->getPath());
    }

    public function getDefaultValue(): mixed {
        return $this->constant->getValue();
    }

    public function isInternal(): bool {
        return (bool) $this->constant->getAttributes(InternalAttribute::class);
    }

    public function isSecret(): bool {
        return (bool) $this->constant->getAttributes(SecretAttribute::class);
    }

    public function isArray(): bool {
        return is_array($this->constant->getValue());
    }

    public function isReadonly(): bool {
        return $this->readonly;
    }

    public function getDescription(): ?string {
        $key  = "settings.descriptions.{$this->getName()}";
        $desc = __($key);

        if ($desc === $key) {
            if ($this->constant->getDocComment()) {
                $doc  = DocBlockFactory::createInstance()->create($this->constant);
                $desc = trim("{$doc->getSummary()}\n\n{$doc->getDescription()}");
            } else {
                $desc = null;
            }
        }

        return $desc;
    }

    public function getGroup(): ?string {
        $group     = null;
        $attribute = $this->getAttribute(Group::class)?->newInstance();

        if ($attribute instanceof Group) {
            $key  = "settings.groups.{$attribute->getName()}";
            $name = __($key);

            if ($key === $name) {
                $group = $attribute->getName();
            } else {
                $group = $name;
            }
        }

        return $group;
    }

    /**
     * @template T of \Attribute
     *
     * @param class-string<T> ...$attributes
     *
     * @return \ReflectionAttribute<T>|null
     */
    protected function getAttribute(string ...$attributes): ?ReflectionAttribute {
        $result = null;

        foreach ($attributes as $attribute) {
            $attrs = $this->constant->getAttributes($attribute);
            $attr  = reset($attrs);

            if ($attr) {
                $result = $attr;
                break;
            }
        }

        return $result;
    }
}
