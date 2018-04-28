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
            return "`{$this->column}` = {$this->values[1]}";
        }

        $valuesString = implode(',', $this->values);
        return "`{$this->column}` IN ($valuesString)";
    }
}


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

class BlockQueryForPosts extends BlockQuery
{
    public function __construct(int $siteId, int ...$postIds)
    {
        assert(!$siteId || (is_multisite() && $siteId));

        $this->siteId = $siteId;
        $this->column = Database::POST_COLUMN;
        $this->values = $postIds;
    }

    public function without(array $exclude): BlockQuery
    {
        $newValues = array_diff($this->values, $exclude);
        return new self($this->siteId, ...$newValues);
    }

    public function getOwnerId(int $id): BlockOwnerId
    {
        return new BlockOwnerIdForPost($this->siteId, $id);
    }
}

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

    public function getOwnerId(int $id): BlockOwnerId
    {
        return new BlockOwnerIdForTerm($this->siteId, $id);
    }
}

class BlockQueryForComments extends BlockQuery
{
    public function __construct(int $siteId, int ...$commentIds)
    {
        assert(!$siteId || (is_multisite() && $siteId));

        $this->siteId = $siteId;
        $this->column = Database::COMMENT_COLUMN;
        $this->values = $commentIds;
    }

    public function without(array $exclude): BlockQuery
    {
        $newValues = array_diff($this->values, $exclude);
        return new self($this->siteId, ...$newValues);
    }

    public function getOwnerId(int $id): BlockOwnerId
    {
        return new BlockOwnerIdForComment($this->siteId, $id);
    }
}

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
