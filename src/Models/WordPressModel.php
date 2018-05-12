<?php
namespace Awful\Models;

use Awful\Models\Database\BlockSet;
use Awful\Models\Database\EntityManager;
use stdClass;

abstract class WordPressModel extends Model
{
    /** @var BlockSet */
    private $blockSet;

    /**
     * Fetches the WordPress object corresponding with `$this->id` object, if
     * one exists.
     *
     * @return \WP_Site|\WP_User|\WP_Post|\WP_Term|\WP_Comment|null
     */
    abstract public function wpObject(): ?object;

    abstract public function siteId(): int;

    abstract public function blockRecordColumn(): string;

    abstract public function blockRecordColumnValue(): int;

    abstract public function rootBlockType(): string;

    abstract public function entityManager(): EntityManager;

    final public function blockSet(): BlockSet
    {
        if ($this->blockSet === null) {
            $this->blockSet = $this->entityManager()->blockSetManager()->fetchBlockSet($this);
        }
        return $this->blockSet;
    }

    final protected function fetchBlockRecord(): stdClass
    {
        return $this->blockSet()->root();
    }
}
