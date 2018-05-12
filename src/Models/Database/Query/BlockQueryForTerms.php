<?php
namespace Awful\Models\Database\Query;

use Awful\Models\Database\Database;

class BlockQueryForTerms extends BlockQuery
{
    public function __construct(int $siteId, int ...$termIds)
    {
        assert(!$siteId || (is_multisite() && $siteId));

        $this->siteId = $siteId;
        $this->column = Database::TERM_COLUMN;
        $this->values = $termIds;
    }

    public function without(array $exclude): BlockQuery
    {
        $newValues = array_diff($this->values, $exclude);
        return new self($this->siteId, ...$newValues);
    }
}
