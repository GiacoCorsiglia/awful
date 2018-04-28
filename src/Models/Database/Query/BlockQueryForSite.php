<?php
namespace Awful\Models\Database\Query;

use Awful\Models\Database\Database;

class BlockQueryForSite extends BlockQuery
{
    public function __construct(int $siteId)
    {
        assert(!$siteId || (is_multisite() && $siteId));

        $this->siteId = $siteId;
        $this->column = Database::SITE_COLUMN;
        $this->values = [1];
    }

    public function ids(): array
    {
        return [$this->siteId];
    }

    public function without(array $exclude): BlockQuery
    {
        $bq = new self($this->siteId);
        $bq->values = [];
        return $bq;
    }

    public function getOwnerId(int $id): BlockOwnerId
    {
        return new BlockOwnerIdForSite($this->siteId);
    }
}
