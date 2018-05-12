<?php
namespace Awful\Models;

use Awful\Models\Database\BlockSet;
use stdClass;

abstract class Block extends Model
{
    /** @var WordPressModel */
    private $owner;

    /** @var BlockSet */
    private $blockSet;

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

    final public function owner(): WordPressModel
    {
        return $this->owner;
    }

    final public function id(): int
    {
        return $this->fetchBlockRecord()->id ?? 0;
    }

    final public function uuid(): string
    {
        return $this->uuid;
    }

    final public function exists(): bool
    {
        return (bool) $this->id();
    }

    final public function blockSet(): BlockSet
    {
        return $this->blockSet;
    }

    final public function fetchBlockRecord(): stdClass
    {
        return $this->blockSet->get($this->uuid) ?: $this->blockSet->createForClass(static::class, $this->uuid);
    }
}
