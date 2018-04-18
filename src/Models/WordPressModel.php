<?php
namespace Awful\Models;

interface WordPressModel
{
    /**
     * The id of this object.
     *
     * @return int
     */
    public function id(): int;

    /**
     * Fetches the WordPress object corresponding with `$this->id` object, if
     * one exists.
     *
     * @return \WP_Network|\WP_Site|\WP_User|\WP_Post|\WP_Term|\WP_Comment|null
     */
    public function wpObject(): ?object;
}
