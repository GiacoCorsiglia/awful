<?php
namespace Awful\Models\Database\Query;

use Awful\Models\Database\Database;
use Awful\Models\Database\Query\Exceptions\EmptyBlockQueryException;

abstract class BlockQuery
{
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

    public function any(): bool
    {
        return (bool) $this->values;
    }

    public function sql(): string
    {
        if (!$this->values) {
            throw new EmptyBlockQueryException();
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
