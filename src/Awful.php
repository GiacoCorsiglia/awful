<?php
namespace Awful;

use Awful\Container\Container;
use Awful\Templates\TemplateEngine;

final class Awful
{
    /** @var Router */
    private $router;

    private function __construct(string $theme_class)
    {
        // Set up dependency injection.
        $container = new Container();

        $theme = $container->get($theme_class);
        if (!($theme instanceof Theme)) {
            throw new TypeError();
        }
        // Make sure this is the only Theme instance anyone gets, regardless of
        // which Theme class they request.
        $container->alias($theme_class, ...class_parents($theme));

        // Bootstrap the Site class and instantiate a Site for the current site,
        // and register it for dependency injection just as we did the theme.
        $site_class = $theme->getSiteClass();
        $site_class::bootstrap();
        $current_site = new $site_class(get_current_blog_id());
        $container->register($current_site, ...class_parents($site_class));

        // Bootstrap the User class and instantiate a User for the current user,
        // and register it for dependency injection just as we did the theme.
        $user_class = $theme->getUserClass();
        $user_class::bootstrap();
        $current_user = new $user_class(wp_get_current_user());
        $container->register($current_user, ...class_parents($user_class));

        // Likewise, ensure that the router can be requested by any of its
        // parent classes.  We won't instantiate it unless this is the frontend.
        $router_class = $theme->getRouterClass();
        $container->alias($router_class, ...class_parents($router_class));

        // Do the same for the Theme's preferred template engine.
        $container->alias($theme->getTemplateEngineClass(), TemplateEngine::class);

        // Register post types and taxonomies.
        foreach ($theme->getPostTypes() as $post_class) {
            $post_class::bootstrap();
        }
        foreach ($theme->getTaxonomies() as $taxonomy_class) {
            $taxonomy_class::bootstrap();
        }

        // Instantiate the hooks requested by the Theme.
        foreach ($theme->getHooks() as $hook_class) {
            $container->get($hook_class);
        }

        // Bootstrap the router if required.
        if (!is_admin()) {
            $this->router = $container->get($router_class);
        }
    }

    public static function bootstrap(string $theme_class): self
    {
        return $GLOBALS['_awful_instance'] = new self($theme_class);
    }

    public function render(): string
    {
        return $this->router->getController()->render();
    }
}
