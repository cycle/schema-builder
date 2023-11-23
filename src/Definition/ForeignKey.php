<?php

declare(strict_types=1);

namespace Cycle\Schema\Definition;

final class ForeignKey
{
    /**
     * @var non-empty-string
     */
    private string $target;

    /**
     * @var array<non-empty-string>
     */
    private array $innerColumns;

    /**
     * @var array<non-empty-string>
     */
    private array $outerColumns;

    private bool $createIndex;

    /**
     * @var non-empty-string
     */
    private string $action;

    /**
     * @param non-empty-string $target
     */
    public function setTarget(string $target): self
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return non-empty-string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @param array<non-empty-string> $columns
     */
    public function setInnerColumns(array $columns): self
    {
        $this->innerColumns = $columns;

        return $this;
    }

    /**
     * @return array<non-empty-string>
     */
    public function getInnerColumns(): array
    {
        return $this->innerColumns;
    }

    /**
     * @param array<non-empty-string> $columns
     */
    public function setOuterColumns(array $columns): self
    {
        $this->outerColumns = $columns;

        return $this;
    }

    /**
     * @return array<non-empty-string>
     */
    public function getOuterColumns(): array
    {
        return $this->outerColumns;
    }

    /**
     * Create an index on innerKey.
     */
    public function createIndex(bool $createIndex = true): self
    {
        $this->createIndex = $createIndex;

        return $this;
    }

    public function isCreateIndex(): bool
    {
        return $this->createIndex;
    }

    /**
     * @param non-empty-string $action
     */
    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return non-empty-string
     */
    public function getAction(): string
    {
        return $this->action;
    }
}
