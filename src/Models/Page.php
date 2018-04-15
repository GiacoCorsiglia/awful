<?php
namespace Awful\Models;

/**
 * Represents the builtin 'page' post type.
 */
class Page extends GenericPost
{
    const TYPE = 'page';

    const IS_BUILTIN = true;
}
