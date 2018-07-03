<?php
namespace Awful\Models;

/**
 * Represents the builtin 'page' post type.
 */
class Page extends GenericPost
{
    const IS_BUILTIN = true;

    const TYPE = 'page';
}
