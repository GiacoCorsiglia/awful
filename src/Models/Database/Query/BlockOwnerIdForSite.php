<?php
namespace Awful\Models\Database\Query;

use Awful\Models\Database\Database;

class BlockOwnerIdForSite extends BlockOwnerId
{
    public function __construct(int $siteId)
    {
        assert(!$siteId || (is_multisite() && $siteId));

        $this->siteId = $siteId;
        $this->column = Database::SITE_COLUMN;
        $this->value = 1;
    }

    public function rootBlockType(): string
    {
        return 'Awful.RootBlocks.Site';
    }
}
