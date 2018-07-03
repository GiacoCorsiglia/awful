<?php
namespace Awful\Models;

/**
 * Represents the builtin 'post' post type.
 */
class Post extends GenericPost
{
    const IS_BUILTIN = true;

    const TYPE = 'post';
}
