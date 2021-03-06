<?php
namespace Awful\Theme;

use Awful\Models\Page;
use Awful\Models\Post;
use Awful\Models\Site;
use Awful\Models\User;

abstract class Theme
{
    /**
     * @return string[]
     * @psalm-return class-string[]
     */
    public function hooks(): array
    {
        return [];
    }

    /**
     * @return string[]
     * @psalm-return class-string[]
     */
    public function postTypes(): array
    {
        return [
            'page' => Page::class,
            'post' => Post::class,
        ];
    }

    /**
     * @return string
     * @psalm-return class-string
     */
    public function siteClass(): string
    {
        return Site::class;
    }

    /**
     * @return string
     * @psalm-return class-string
     */
    public function userClass(): string
    {
        return User::class;
    }
}
