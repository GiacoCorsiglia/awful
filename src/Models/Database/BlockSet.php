<?php
namespace Awful\Models\Database;

use Awful\Models\Database\Query\BlockOwnerId;
use stdClass;
use function Awful\uuid;

/**
 * Represents the set of blocks owned by a single object.
 */
class BlockSet
{
    /** @var BlockSetManager */
    private $blockSetManager;

    /** @var BlockOwnerId */
    private $ownerId;

    /** @var array */
    private $blocks;

    public function __construct(
        BlockSetManager $blockSetManager,
        BlockOwnerId $ownerId,
        array $blocks
    ) {
        $this->blockSetManager = $blockSetManager;

        $this->ownerId = $ownerId;

        $this->blocks = [];
        foreach ($blocks as $block) {
            $this->blocks[$block->uuid] = $block;
        }
    }

    public function manager(): BlockSetManager
    {
        return $this->blockSetManager;
    }

    public function all(): array
    {
        return $this->blocks;
    }

    public function get(string $uuid): ?stdClass
    {
        return $this->blocks[$uuid] ?? null;
    }

    public function set(string $uuid, array $data): void
    {
        if (!isset($this->blocks[$uuid])) {
            throw new \Exception();
        }

        $this->blocks[$uuid]->data = $data;
    }

    public function create(string $type, array $data = [], string $uuid = ''): stdClass
    {
        if ($uuid && isset($this->blocks[$uuid])) {
            throw new \Exception();
        }
        $uuid = $uuid ?: uuid();
        $this->blocks[$uuid] = (object) [
            $this->ownerId->column() => $this->ownerId->value(),
            'type' => $type,
            'data' => $data,
        ];
        return $this->blocks[$uuid];
    }

    /**
     * @param string $class
     * @psalm-param class-string $class
     * @param  string   $uuid
     * @return stdClass
     */
    public function createForClass(string $class, string $uuid): stdClass
    {
        $type = $this->blockSetManager->blockTypeMap()->typeForClass($class);
        return $this->create($type, [], $uuid);
    }

    public function root(): stdClass
    {
        $type = $this->ownerId->rootBlockType();

        return $this->firstOfType($type) ?: $this->create($type);
    }

    public function save(): void
    {
        $this->blockSetManager->save($this);
    }

    public function ownerId(): BlockOwnerId
    {
        return $this->ownerId;
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
