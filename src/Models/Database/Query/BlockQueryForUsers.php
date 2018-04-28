<?php
namespace Awful\Models\Database\Query;

use Awful\Models\Database\Database;

class BlockQueryForUsers extends BlockQuery
{
    public function __construct(int ...$userIds)
    {
        $this->siteId = is_multisite() ? 1 : 0;
        $this->column = Database::USER_COLUMN;
        $this->values = $userIds;
    }

    public function without(array $exclude): BlockQuery
    {
        $newValues = array_diff($this->values, $exclude);
        return new self(...$newValues);
    }

    public function getOwnerId(int $id): BlockOwnerId
    {
        return new BlockOwnerIdForUser($id);
    }
}
