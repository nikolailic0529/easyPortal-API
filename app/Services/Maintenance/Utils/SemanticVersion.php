<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Utils;

use InvalidArgumentException;
use Stringable;

use function preg_match;
use function sprintf;

/**
 * @link https://semver.org/
 */
class SemanticVersion implements Stringable {
    protected const REGEXP            = <<<'REGEXP'
        /
        ^
            (?P<majorVersion>[0-9]+)
            \.
            (?P<minorVersion>[0-9]+)
            \.
            (?P<patchVersion>[0-9]+)
            (?:-(?P<preRelease>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?
            (?:\+(?P<metadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?
        $
        /ix
        REGEXP;
    protected const REGEXP_VERSION    = '/^[0-9]+$/i';
    protected const REGEXP_PRERELEASE = '/^[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*$/i';
    protected const REGEXP_METADATA   = '/^[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*$/i';

    private string  $majorVersion;
    private string  $minorVersion;
    private string  $patchVersion;
    private ?string $preRelease;
    private ?string $metadata;

    public function __construct(string $version) {
        if (preg_match(self::REGEXP, $version, $matches)) {
            $this->majorVersion = $matches['majorVersion'];
            $this->minorVersion = $matches['minorVersion'] ?? '0';
            $this->patchVersion = $matches['patchVersion'] ?? '0';
            $this->preRelease   = $matches['preRelease'] ?? null;
            $this->metadata     = $matches['metadata'] ?? null;
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `%s` is not a valid Semantic Version string.',
                $version,
            ));
        }
    }

    public function getMajorVersion(): string {
        return $this->majorVersion;
    }

    public function setMajorVersion(string $majorVersion): static {
        if (!preg_match(self::REGEXP_VERSION, $majorVersion)) {
            throw new InvalidArgumentException(sprintf(
                'The `%s` is not a valid Major version.',
                $majorVersion,
            ));
        }

        $this->majorVersion = $majorVersion;

        return $this;
    }

    public function getMinorVersion(): string {
        return $this->minorVersion;
    }

    public function setMinorVersion(string $minorVersion): static {
        if (!preg_match(self::REGEXP_VERSION, $minorVersion)) {
            throw new InvalidArgumentException(sprintf(
                'The `%s` is not a valid Minor version.',
                $minorVersion,
            ));
        }

        $this->minorVersion = $minorVersion;

        return $this;
    }

    public function getPatchVersion(): string {
        return $this->patchVersion;
    }

    public function setPatchVersion(string $patchVersion): static {
        if (!preg_match(self::REGEXP_VERSION, $patchVersion)) {
            throw new InvalidArgumentException(sprintf(
                'The `%s` is not a valid Patch version.',
                $patchVersion,
            ));
        }

        $this->patchVersion = $patchVersion;

        return $this;
    }

    public function getPreRelease(): ?string {
        return $this->preRelease;
    }

    public function setPreRelease(?string $preRelease): static {
        if ($preRelease !== null && !preg_match(self::REGEXP_PRERELEASE, $preRelease)) {
            throw new InvalidArgumentException(sprintf(
                'The `%s` is not a valid pre-release.',
                $preRelease,
            ));
        }

        $this->preRelease = $preRelease;

        return $this;
    }

    public function getMetadata(): ?string {
        return $this->metadata;
    }

    public function setMetadata(?string $metadata): static {
        if ($metadata !== null && !preg_match(self::REGEXP_METADATA, $metadata)) {
            throw new InvalidArgumentException(sprintf(
                'The `%s` is not a valid metadata.',
                $metadata,
            ));
        }

        $this->metadata = $metadata;

        return $this;
    }

    public function __toString(): string {
        $version = "{$this->getMajorVersion()}.{$this->getMinorVersion()}.{$this->getPatchVersion()}";

        if ($this->getPreRelease()) {
            $version .= "-{$this->getPreRelease()}";
        }

        if ($this->getMetadata()) {
            $version .= "+{$this->getMetadata()}";
        }

        return $version;
    }
}
