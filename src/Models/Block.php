<?php
namespace Awful\Models;

use Awful\Models\Database\BlockSet;
use stdClass;

abstract class Block extends Model
{
    /** @var int */
    private $id;

    /** @var string */
    private $uuid;

    /** @var WordPressModel */
    private $owner;

    public function __construct(
        WordPressModel $owner,
        int $id,
        string $uuid
    ) {
        $this->owner = $owner;
        $this->id = $id;
        $this->uuid = $uuid;
        $this->initializeBlockSet($owner->blockSet());
    }

    public function owner(): WordPressModel
    {
        return $this->owner;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function exists(): bool
    {
        return $this->id && $this->owner->exists();
    }

    final public function fetchBlock(BlockSet $blockSet): stdClass
    {
        return $blockSet->get($this->uuid) ?: $blockSet->createForClass(static::class, $this->uuid);
    }
}
