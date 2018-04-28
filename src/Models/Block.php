<?php
namespace Awful\Models;

use Awful\Models\Database\BlockSet;
use stdClass;

abstract class Block extends Model
{
    /** @var string */
    private $uuid;

    /** @var WordPressModel */
    private $owner;

    public function __construct(
        WordPressModel $owner,
        string $uuid
    ) {
        $this->owner = $owner;
        $this->uuid = $uuid;
        $this->initializeBlockSet($owner->blockSet());
    }

    public function owner(): WordPressModel
    {
        return $this->owner;
    }

    public function id(): int
    {
        return $this->fetchBlockRecord($this->blockSet())->id ?? 0;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function exists(): bool
    {
        return (bool) $this->id();
    }

    final public function fetchBlockRecord(BlockSet $blockSet): stdClass
    {
        return $blockSet->get($this->uuid) ?: $blockSet->createForClass(static::class, $this->uuid);
    }
}
