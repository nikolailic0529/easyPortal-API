<?php declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Exception\InvalidArgumentException;

class OutlineRow
{
    private bool $visible;
    private bool $collapsed;

    /**
     * @var int<0, 7>
     */
    private int $outlineLevel;

    /**
     * @param int<0, 7> $outlineLevel
     */
    public function __construct(int $outlineLevel, bool $collapsed = false, bool $visible = true)
    {
        $this->setOutlineLevel($outlineLevel);
        $this->setCollapsed($collapsed);
        $this->setVisible($visible);
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function setCollapsed(bool $collapsed): static
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    /**
     * @return int<0, 7>
     */
    public function getOutlineLevel(): int
    {
        return $this->outlineLevel;
    }

    /**
     * @param int<0, 7> $outlineLevel
     */
    public function setOutlineLevel(int $outlineLevel): static
    {
        if ($outlineLevel < 0 || $outlineLevel > 7) {
            throw new InvalidArgumentException('OutlineRow level must range between 0 and 7.');
        }

        $this->outlineLevel = $outlineLevel;

        return $this;
    }
}
