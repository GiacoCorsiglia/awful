<?php
namespace Awful\Providers;

use Awful\Models\Page;
use Awful\Models\Post;

/**
 * A simple provider which registers the WordPress default post types and
 * taxonomies with Awful.
 */
class DefaultTheme extends Provider
{
    public function configure(): void
    {
        $this->awful->registerModels([
            Post::class,
            Page::class,
        ]);
    }
}
