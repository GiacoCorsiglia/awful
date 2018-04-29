<?php
namespace Awful\Models\Database\Query;

use Awful\Models\Database\Database;

class BlockOwnerIdForComment extends BlockOwnerId
{
    public function __construct(int $siteId, int $commentId)
    {
        assert(!$siteId || (is_multisite() && $siteId));

        $this->siteId = $siteId;
        $this->column = Database::COMMENT_COLUMN;
        $this->value = $commentId;
    }

    public function toBlockQuery(): BlockQuery
    {
        return new BlockQueryForComments($this->siteId, $this->value);
    }

    public function rootBlockType(): string
    {
        return 'Awful.RootBlocks.Comment';
    }
}
