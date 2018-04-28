<?php
namespace Awful\Models\Database\Query;

use Awful\Models\Database\Database;

abstract class BlockOwnerId
{
    /** @var int */
    protected $siteId;

    /** @var string */
    protected $column;

    /** @var int */
    protected $value;

    public function siteId(): int
    {
        return $this->siteId;
    }

    public function column(): string
    {
        return $this->column;
    }

    public function value(): int
    {
        return $this->value;
    }

    public function sql(): string
    {
        if (!$this->value) {
            throw new \Exception();
        }

        if (!in_array($this->column, Database::FOREIGN_KEY_COLUMNS)) {
            throw new \Exception();
        }

        $this->value = (int) $this->value;
        return "`{$this->column}` = {$this->value}";
    }

    abstract public function rootBlockType(): string;
}

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

class BlockOwnerIdForPost extends BlockOwnerId
{
    public function __construct(int $siteId, int $postId)
    {
        assert(!$siteId || (is_multisite() && $siteId));

        $this->siteId = $siteId;
        $this->column = Database::POST_COLUMN;
        $this->value = $postId;
    }

    public function rootBlockType(): string
    {
        return 'Awful.RootBlocks.Post';
    }
}

class BlockOwnerIdForTerm extends BlockOwnerId
{
    public function __construct(int $siteId, int $termId)
    {
        assert(!$siteId || (is_multisite() && $siteId));

        $this->siteId = $siteId;
        $this->column = Database::TERM_COLUMN;
        $this->value = $termId;
    }

    public function rootBlockType(): string
    {
        return 'Awful.RootBlocks.Term';
    }
}

class BlockOwnerIdForComment extends BlockOwnerId
{
    public function __construct(int $siteId, int $commentId)
    {
        assert(!$siteId || (is_multisite() && $siteId));

        $this->siteId = $siteId;
        $this->column = Database::COMMENT_COLUMN;
        $this->value = $commentId;
    }

    public function rootBlockType(): string
    {
        return 'Awful.RootBlocks.Comment';
    }
}

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
