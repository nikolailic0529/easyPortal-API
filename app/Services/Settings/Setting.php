<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Queue\CronJob;
use App\Services\Queue\Job;
use App\Services\Settings\Attributes\Group as GroupAttribute;
use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Job as JobAttribute;
use App\Services\Settings\Attributes\PublicName;
use App\Services\Settings\Attributes\Secret as SecretAttribute;
use App\Services\Settings\Attributes\Service as ServiceAttribute;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Attributes\Type as TypeAttribute;
use App\Services\Settings\Types\BooleanType;
use App\Services\Settings\Types\FloatType;
use App\Services\Settings\Types\IntType;
use App\Services\Settings\Types\StringType;
use App\Services\Settings\Types\Type;
use App\Utils\Cast;
use App\Utils\Description;
use Attribute;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClassConstant;

use function gettype;
use function implode;
use function is_array;
use function reset;
use function sprintf;
use function trans;

class Setting {
    protected SettingAttribute $definition;

    public function __construct(
        protected ReflectionClassConstant $constant,
    ) {
        $attributes = [
            ServiceAttribute::class,
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

    public function getPath(): ?string {
        return $this->definition->getPath();
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

    public function getDescription(): ?string {
        $key  = "settings.descriptions.{$this->getName()}";
        $desc = Cast::toString(trans($key));

        if ($desc === $key) {
            $desc = (new Description())->get($this->constant);
        }

        return $desc;
    }

    public function getGroup(): ?string {
        $group     = null;
        $attribute = $this->getAttribute(GroupAttribute::class)?->newInstance();

        if ($attribute instanceof GroupAttribute) {
            $key  = "settings.groups.{$attribute->getName()}";
            $name = Cast::toString(trans($key));

            if ($key === $name) {
                $group = $attribute->getName();
            } else {
                $group = $name;
            }
        }

        return $group;
    }

    public function isJob(): bool {
        return $this->definition instanceof JobAttribute;
    }

    /**
     * @return class-string<Job>|null
     */
    public function getJob(): ?string {
        return $this->isJob() && $this->definition instanceof JobAttribute
            ? $this->definition->getClass()
            : null;
    }

    public function isService(): bool {
        return $this->definition instanceof ServiceAttribute;
    }

    /**
     * @return class-string<CronJob>|null
     */
    public function getService(): ?string {
        return $this->isService() && $this->definition instanceof ServiceAttribute
            ? $this->definition->getClass()
            : null;
    }

    public function isPublic(): bool {
        return (bool) $this->getAttribute(PublicName::class);
    }

    public function getPublicName(): ?string {
        $attr = $this->getAttribute(PublicName::class)?->newInstance();
        $name = $attr instanceof PublicName
            ? $attr->getName()
            : null;

        return $name;
    }

    /**
     * @template T of Attribute
     *
     * @param class-string<T> ...$attributes
     *
     * @return ReflectionAttribute<T>|null
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
