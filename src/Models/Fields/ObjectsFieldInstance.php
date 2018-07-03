<?php
namespace Awful\Models\Fields;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;

abstract class ObjectsFieldInstance implements ArrayAccess, IteratorAggregate, Countable
{
    /** @var array */
    protected $ids = [];

    /** @var object[] */
    protected $objects = [];

    public function any(): bool
    {
        return (bool) $this->ids;
    }

    public function count(): int
    {
        return count($this->objects);
    }

    public function first(): ?object
    {
        return $this->objects[0] ?? null;
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->objects);
    }

    public function ids(): array
    {
        return $this->ids;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->objects[$offset]);
    }

    public function offsetGet($offset): ?object
    {
        return $this->objects[$offset] ?? null;
    }

    public function offsetSet($offset, $object): void
    {
        $id = $this->validateAndGetId($object);

        if ($offset === null) {
            $this->objects[] = $object;
            $this->ids[] = $id;
        } else {
            $this->objects[$offset] = $object;
            $this->ids[$offset] = $id;
        }
        $this->emit();
    }

    public function offsetUnset($offset): void
    {
        unset($this->objects[$offset]);
        unset($this->ids[$offset]);
        $this->emit();
    }

    abstract protected function emit(): void;

    abstract protected function validateAndGetId(object $object);
}
