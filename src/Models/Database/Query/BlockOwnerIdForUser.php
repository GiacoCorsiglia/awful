<?php
namespace Awful\Models\Database\Query;

use Awful\Models\Database\Database;

class BlockOwnerIdForUser extends BlockOwnerId
{
    public function __construct(int $userId)
    {
        $this->siteId = is_multisite() ? 1 : 0;
        $this->column = Database::USER_COLUMN;
        $this->value = $userId;
    }

    public function rootBlockType(): string
    {
        return 'Awful.RootBlocks.User';
    }
}
