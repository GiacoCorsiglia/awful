<?php
namespace Awful\Models;

abstract class Block extends Model
{
    /** @var string */
    private $uuid;

    /** @var BlockOwnerModel */
    private $owner;

    public function __construct(
        BlockOwnerModel $owner,
        string $uuid = '',
        array $data = []
    ) {
        $this->owner = $owner;
        $this->uuid = $uuid;
        $this->initializeData($data);
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function exists(): bool
    {
        return $this->owner->exists();
    }
}
