<?php
namespace Awful;

use Awful\Models\Site;
use Awful\Templates\Twig\Extensions\General;
use Awful\Templates\Twig\TwigTemplateEngine;

/**
 * Basically a configuration object.
 */
abstract class Theme
{
    protected const SITE_CLASS = Site::class;

    protected const USER_CLASS = User::class;

    protected const ROUTER_CLASS = Router::class;

    protected const TEMPLATE_ENGINE_CLASS = TwigTemplateEngine::class;

    protected const TEMPLATE_DIRECTORY = '';

    public function getName(): string
    {
        return '';
    }

    public function getPostTypes(): array
    {
        return [];
    }

    public function getTaxonomies(): array
    {
        return [];
    }

    public function getHooks(): array
    {
        return [];
    }

    public function getSiteClass(): string
    {
        return static::SITE_CLASS;
    }

    public function getUserClass(): string
    {
        return static::USER_CLASS;
    }

    public function getRouterClass(): string
    {
        return static::ROUTER_CLASS;
    }

    public function getTemplateEngineClass(): string
    {
        return static::TEMPLATE_ENGINE_CLASS;
    }

    public function getTemplateDirectory(): string
    {
        return static::TEMPLATE_DIRECTORY;
    }

    public function getTwigExtensions(): array
    {
        return [
            General::class,
        ];
    }
}
