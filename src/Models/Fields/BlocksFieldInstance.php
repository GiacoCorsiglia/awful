<?php
namespace Awful\Models\Field;

use ArrayAccess;
use ArrayIterator;
use Awful\Models\Block;
use Awful\Models\BlockOwnerModel;
use Countable;
use Iterator;
use IteratorAggregate;

class BlocksFieldInstance implements ArrayAccess, IteratorAggregate, Countable
{
    /** @var string[] */
    private $uuids;

    /** @var Block[] */
    private $blocks;

    /** @var BlockOwnerModel */
    private $owner;

    /** @var string */
    private $fieldName;

    public function __construct(
        array $uuids,
        BlockOwnerModel $owner,
        string $fieldName
    ) {
        $this->owner = $owner;
        $this->fieldName = $fieldName;

        $blockSet = $owner->blockSet();
        $this->uuids = [];
        $this->blocks = [];

        foreach ($uuids as $uuid) {
            $rawBlock = $blockSet->get($uuid);
            if (!$rawBlock) {
                continue;
            }
            $this->uuids[] = $uuid;
            // TODO:
            $rawBlock->block_type;
            $rawBlock->block_data;
            // $this->blocks = new Block($owner);
        }
    }

    public function count(): int
    {
        return count($this->blocks);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->blocks);
    }

    public function offsetSet($offset, $block): void
    {
        if ($offset === null) {
            $this->blocks[] = $block;
            $this->uuids[] = $block->uuid();
        } else {
            $this->blocks[$offset] = $block;
            $this->uuids[$offset] = $block->uuid();
        }
        $this->emit();
    }

    public function offsetExists($offset): bool
    {
        return isset($this->blocks[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->blocks[$offset]);
        unset($this->uuids[$offset]);
        $this->emit();
    }

    public function offsetGet($offset): ?Block
    {
        return $this->blocks[$offset] ?? null;
    }

    public function uuids(): array
    {
        return $this->uuids;
    }

    public function any(): bool
    {
        return (bool) $this->uuids;
    }

    private function emit(): void
    {
        $this->owner->set($this->fieldName, $this->uuids);
    }
}
