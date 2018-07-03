<?php
namespace Awful\Models;

use Awful\Models\Database\BlockSet;
use stdClass;

abstract class Block extends Model
{
    /** @var BlockSet */
    private $blockSet;

    /** @var WordPressModel */
    private $owner;

    /** @var string */
    private $uuid;

    final public function __construct(
        WordPressModel $owner,
        string $uuid
    ) {
        $this->owner = $owner;
        $this->blockSet = $owner->blockSet();
        $this->uuid = $uuid;
    }

    final public function blockSet(): BlockSet
    {
        return $this->blockSet;
    }

    final public function exists(): bool
    {
        return (bool) $this->id();
    }

    final public function id(): int
    {
        return $this->fetchBlockRecord()->id ?? 0;
    }

    final public function owner(): WordPressModel
    {
        return $this->owner;
    }

    final public function reloadBlocks(): void
    {
        $this->blockSet = $this->owner->blockSet();
        parent::reloadBlocks();
    }

    final public function uuid(): string
    {
        return $this->uuid;
    }

    final protected function fetchBlockRecord(): stdClass
    {
        return $this->blockSet->get($this->uuid) ?: $this->blockSet->createForClass(static::class, $this->uuid);
    }
}
