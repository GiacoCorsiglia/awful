<?php
namespace Awful\Models\Database;

use Awful\Models\Database\Exceptions\BlockNotFoundException;
use Awful\Models\Database\Exceptions\UuidCollisionException;
use Awful\Models\Database\Map\BlockTypeMap;
use Awful\Models\WordPressModel;
use stdClass;
use function Awful\uuid;

/**
 * Represents the set of blocks owned by a single object.
 */
class BlockSet
{
    /**
     * @var stdClass[]
     * @psalm-var array<string, stdClass>
     */
    private $blocks;

    /** @var BlockTypeMap */
    private $blockTypeMap;

    /** @var WordPressModel */
    private $owner;

    /**
     * @param BlockTypeMap $blockTypeMap
     * @param WordPressModel $owner
     * @param stdClass[] $blocks
     * @psalm-param array<string|int, stdClass> $blocks
     */
    public function __construct(
        BlockTypeMap $blockTypeMap,
        WordPressModel $owner,
        array $blocks
    ) {
        $this->blockTypeMap = $blockTypeMap;

        $this->owner = $owner;

        $this->blocks = [];
        foreach ($blocks as $block) {
            $this->blocks[$block->uuid] = $block;
        }
    }

    /**
     * @return array<string, stdClass>
     */
    public function all(): array
    {
        return $this->blocks;
    }

    public function blockTypeMap(): BlockTypeMap
    {
        return $this->blockTypeMap;
    }

    public function create(string $type, array $data = [], string $uuid = ''): stdClass
    {
        if ($uuid && isset($this->blocks[$uuid])) {
            throw new UuidCollisionException($uuid);
        }
        $uuid = $uuid ?: uuid();
        $this->blocks[$uuid] = (object) [
            'uuid' => $uuid,
            $this->owner->blockRecordColumn() => $this->owner->blockRecordColumnValue(),
            'type' => $type,
            'data' => $data,
        ];
        return $this->blocks[$uuid];
    }

    /**
     * @param string $class
     * @psalm-param class-string $class
     * @param string $uuid
     *
     * @return stdClass
     */
    public function createForClass(string $class, string $uuid): stdClass
    {
        $type = $this->blockTypeMap->typeForClass($class);
        return $this->create($type, [], $uuid);
    }

    public function get(string $uuid): ?stdClass
    {
        return $this->blocks[$uuid] ?? null;
    }

    public function owner(): WordPressModel
    {
        return $this->owner;
    }

    public function root(): stdClass
    {
        $type = $this->owner->rootBlockType();
        return $this->firstOfType($type) ?: $this->create($type);
    }

    public function set(string $uuid, array $data): void
    {
        if (!isset($this->blocks[$uuid])) {
            throw new BlockNotFoundException($uuid);
        }

        $this->blocks[$uuid]->data = $data;
    }

    private function firstOfType(string $type): ?stdClass
    {
        foreach ($this->blocks as $block) {
            if ($block->type === $type) {
                return $block;
            }
        }
        return null;
    }
}
