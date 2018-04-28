<?php
namespace Awful\Models\Database\Query;

use Awful\Models\Database\Database;

abstract class BlockQuery
{
    public static function fromOwnerId(BlockOwnerId $id): self
    {
        if ($id instanceof BlockOwnerIdForSite) {
            return new BlockQueryForSite($id->siteId());
        }
        if ($id instanceof BlockOwnerIdForUser) {
            return new BlockQueryForUsers($id->value());
        }
        if ($id instanceof BlockOwnerIdForPost) {
            return new BlockQueryForPosts($id->siteId(), $id->value());
        }
        if ($id instanceof BlockOwnerIdForTerm) {
            return new BlockQueryForTerms($id->siteId(), $id->value());
        }
        if ($id instanceof BlockOwnerIdForComment) {
            return new BlockQueryForComments($id->siteId(), $id->value());
        }
        throw new \Exception();
    }

    /** @var int */
    protected $siteId;

    /** @var string */
    protected $column;

    /** @var array */
    protected $values;

    public function siteId(): int
    {
        return $this->siteId;
    }

    public function column(): string
    {
        return $this->column;
    }

    public function values(): array
    {
        return $this->values;
    }

    public function ids(): array
    {
        return $this->values;
    }

    abstract public function without(array $exclude): self;

    abstract public function getOwnerId(int $id): BlockOwnerId;

    public function any(): bool
    {
        return (bool) $this->values;
    }

    public function sql(): string
    {
        if (!$this->values) {
            throw new \Exception();
        }

        if (!in_array($this->column, Database::FOREIGN_KEY_COLUMNS)) {
            throw new \Exception();
        }

        foreach ($this->values as $k => $v) {
            $this->values[$k] = (int) $v;
        }

        if (count($this->values) === 1) {
            return "`{$this->column}` = {$this->values[0]}";
        }

        $valuesString = implode(',', $this->values);
        return "`{$this->column}` IN ($valuesString)";
    }
}
