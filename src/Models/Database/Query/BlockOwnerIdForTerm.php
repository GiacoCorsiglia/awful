<?php
namespace Awful\Models\Database\Query;

use Awful\Models\Database\Database;

class BlockOwnerIdForTerm extends BlockOwnerId
{
    public function __construct(int $siteId, int $termId)
    {
        assert(!$siteId || (is_multisite() && $siteId));

        $this->siteId = $siteId;
        $this->column = Database::TERM_COLUMN;
        $this->value = $termId;
    }

    public function toBlockQuery(): BlockQuery
    {
        return new BlockQueryForTerms($this->siteId, $this->value);
    }

    public function rootBlockType(): string
    {
        return 'Awful.RootBlocks.Term';
    }
}
