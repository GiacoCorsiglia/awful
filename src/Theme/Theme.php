<?php
namespace Awful\Theme;

abstract class Theme
{
    public function models(): array
    {
        return [];
    }

    public function hooks(): array
    {
        return [];
    }

    public function userClass(): string
    {
        return '';
    }

    public function siteClass(): string
    {
        return '';
    }
}
