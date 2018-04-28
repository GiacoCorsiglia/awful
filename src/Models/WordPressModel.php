<?php
namespace Awful\Models;

use Awful\Models\Database\BlockSet;
use stdClass;

abstract class WordPressModel extends Model
{
    /**
     * Fetches the WordPress object corresponding with `$this->id` object, if
     * one exists.
     *
     * @return \WP_Site|\WP_User|\WP_Post|\WP_Term|\WP_Comment|null
     */
    abstract public function wpObject(): ?object;

    final protected function fetchBlock(BlockSet $blockSet): stdClass
    {
        return $blockSet->root();
    }
}
