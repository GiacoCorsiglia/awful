<?php
namespace Awful\Models\Database\Query;

use Awful\Models\Database\Database;

class BlockOwnerIdForPost extends BlockOwnerId
{
    public function __construct(int $siteId, int $postId)
    {
        assert(!$siteId || (is_multisite() && $siteId));

        $this->siteId = $siteId;
        $this->column = Database::POST_COLUMN;
        $this->value = $postId;
    }

    public function toBlockQuery(): BlockQuery
    {
        return new BlockQueryForPosts($this->siteId, $this->value);
    }

    public function rootBlockType(): string
    {
        return 'Awful.RootBlocks.Post';
    }
}
