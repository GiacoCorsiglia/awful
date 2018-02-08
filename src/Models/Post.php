<?php
namespace Awful\Models;

/**
 * Represents the builtin 'post' post type.
 */
class Post extends GenericPost
{
    const TYPE = 'post';

    const IS_BUILTIN = true;
}
