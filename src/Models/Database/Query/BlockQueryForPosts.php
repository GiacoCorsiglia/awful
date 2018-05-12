<?php
namespace Awful\Models\Database\Query;

use Awful\Models\Database\Database;

class BlockQueryForPosts extends BlockQuery
{
    public function __construct(int $siteId, int ...$postIds)
    {
        assert(!$siteId || (is_multisite() && $siteId));

        $this->siteId = $siteId;
        $this->column = Database::POST_COLUMN;
        $this->values = $postIds;
    }

    public function without(array $exclude): BlockQuery
    {
        $newValues = array_diff($this->values, $exclude);
        return new self($this->siteId, ...$newValues);
    }
}
