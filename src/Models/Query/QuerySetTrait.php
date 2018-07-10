<?php
namespace Awful\Models\Query;

use ArrayIterator;
use Awful\Exceptions\ImmutabilityException;

trait QuerySetTrait
{
    /** @var array|null */
    private $objects;

    public function any(): bool
    {
        return (bool) $this->count();
    }

    public function array(): array
    {
        if ($this->objects === null) {
            $this->objects = $this->fetch();
        }
        return $this->objects;
    }

    public function count(): int
    {
        if ($this->objects === null) {
            $this->objects = $this->fetch();
        }
        return count($this->objects);
    }

    abstract public function fetch(): array;

    public function first(): ?object
    {
        if ($this->objects === null) {
            $this->objects = $this->fetch();
        }
        return reset($this->objects);
    }

    public function getIterator(): ArrayIterator
    {
        if ($this->objects === null) {
            $this->objects = $this->fetch();
        }
        return new ArrayIterator($this->objects);
    }

    public function offsetExists($objectId): bool
    {
        if ($this->objects === null) {
            $this->objects = $this->fetch();
        }
        return isset($this->objects[$objectId]);
    }

    public function offsetGet($objectId): ?object
    {
        if ($this->objects === null) {
            $this->objects = $this->fetch();
        }
        return $this->objects[$objectId] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        throw new ImmutabilityException();
    }

    public function offsetUnset($offset): void
    {
        throw new ImmutabilityException();
    }
}
