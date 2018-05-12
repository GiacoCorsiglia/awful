<?php
namespace Awful\Models\Database\Query;

class BlockQueryForSingleObject extends BlockQuery
{
    public function __construct(
        int $siteId,
        string $column,
        int $value
    ) {
        $this->siteId = $siteId;
        $this->column = $column;
        $this->values = [$value];
    }

    public function without(array $exclude): BlockQuery
    {
        // TODO: This is dumb and could fail in some cases.
        $bq = new self($this->siteId, $this->column, $this->values[0]);
        if (in_array($this->values[0], $exclude)) {
            $bq->values = [];
        }
        return $bq;
    }
}
