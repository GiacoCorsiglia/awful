<?php
namespace Awful\Models;

use stdClass;

class BlockSet
{
    /** @var array */
    private $blocks = [];

    public function __construct(array $blocks)
    {
        foreach ($blocks as $block) {
            $this->blocks[$block->uuid] = $block;
        }
    }

    public function get(string $uuid): ?stdClass
    {
        return $this->blocks[$uuid] ?? null;
    }

    public function firstOfType(string $type): ?stdClass
    {
        foreach ($this->blocks as $block) {
            if ($block->type === $type) {
                return $block;
            }
        }
        return null;
    }
}
