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
