<?php
namespace Awful\Templates\Twig;

use Awful\Container\Container;
use Awful\Templates\TemplateEngine;
use Awful\Theme;
use Twig_Environment;
use Twig_Loader_Filesystem;

/**
 * Simple helper for rendering templates using Twig.
 */
class TwigTemplateEngine extends TemplateEngine
{
    /** @var Container */
    protected $container;

    /** @var Theme */
    protected $theme;

    /** @var Twig_Environment|null */
    private $twig;

    /**
     * @param Container $container
     * @param Theme     $theme
     */
    public function __construct(Container $container, Theme $theme)
    {
        $this->container = $container;
        $this->theme = $theme;
    }

    public function render(string $template, array $context = []): string
    {
        return $this->getTwig()->render($template, $context);
    }

    /**
     * Gets an instance of twig to render templates with, based on config in the
     * current Theme.
     *
     * @psalm-suppress InvalidNullableReturnType
     * @psalm-suppress UndefinedConstant
     *
     * @return Twig_Environment
     */
    protected function getTwig(): Twig_Environment
    {
        if (!$this->twig) {
            $loader = new Twig_Loader_Filesystem($this->theme->getTemplateDirectory());

            $this->twig = new Twig_Environment($loader, [
                // TODO: these constants don't exist.
                'cache' => WP_ENV !== 'dev' ? (CACHE_DIR . '/twig-cache/') : false,
                'autoescape' => false, // We should reconsider this perhaps
            ]);

            foreach ($this->theme->getTwigExtensions() as $ext_class) {
                $this->twig->addExtension($this->container->get($ext_class));
            }
        }

        return $this->twig;
    }
}
