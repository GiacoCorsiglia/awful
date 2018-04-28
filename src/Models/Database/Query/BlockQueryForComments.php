<?php
namespace Awful\Models\Database\Query;

use Awful\Models\Database\Database;

class BlockQueryForComments extends BlockQuery
{
    public function __construct(int $siteId, int ...$commentIds)
    {
        assert(!$siteId || (is_multisite() && $siteId));

        $this->siteId = $siteId;
        $this->column = Database::COMMENT_COLUMN;
        $this->values = $commentIds;
    }

    public function without(array $exclude): BlockQuery
    {
        $newValues = array_diff($this->values, $exclude);
        return new self($this->siteId, ...$newValues);
    }

    public function getOwnerId(int $id): BlockOwnerId
    {
        return new BlockOwnerIdForComment($this->siteId, $id);
    }
}
