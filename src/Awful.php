<?php
namespace Awful;

use Awful\Container\Container;
use Awful\Models\Fields\FieldsRegistrar;
use Awful\Models\Fields\FieldsResolver;
use Awful\Models\HasFields;
use Awful\Models\Model;
use Awful\Models\Network;
use Awful\Models\Site;
use Awful\Models\User;
use Awful\Providers\Provider;
use Awful\Templates\TemplateEngine;

final class Awful
{
    public static function bootstrap(array $providers): self
    {
        return $GLOBALS['_awful_instance'] = new self($providers);
    }

    /** @var Router|null */
    private $router;

    /** @var string[] */
    private $models = [];

    /** @var string[] */
    private $hooks = [];

    /** @var string|null */
    private $template_engine_class;

    /** @var string|null */
    private $site_class;

    /** @var string|null */
    private $network_class;

    /** @var string|null */
    private $user_class;

    private function __construct(array $provider_classes)
    {
        assert((bool) $provider_classes, 'Expected at least one provider class');
        assert(every($provider_classes, 'is_subclass_of', [Provider::class]), 'Expected array of `Provider` subclasses');

        $container = new Container();

        foreach ($provider_classes as $provider_class) {
            (new $provider_class($this, $container))->configure();
        }


        $fields_resolver = $container->get(FieldsResolver::class);
        HasFields::setDefaultFieldsResolver($fields_resolver);


        $site_class = $this->site_class ?: Site::class;
        $current_site = Site::id(get_current_blog_id(), 0, $fields_resolver);
        $container->register($current_site, Site::class);

        $user_class = $this->user_class ?: User::class;
        $current_user = User::id(get_current_user_id(), 0, $fields_resolver);
        $container->register($current_user, User::class);

        $network_class = $this->network_class ?: Network::class;
        $current_user = Network::id(wp_get_network()->id ?? 0, 0, $fields_resolver);
        $container->register($current_user, Network::class);


        $field_sets = array_merge(
            $site_class::getOptionsPages(),
            [$user_class],
            $this->models
        );
        $fields_registrar = $container->get(FieldsRegistrar::class);
        foreach ($field_sets as $field_set) {
            // TODO
            $fields_registrar->register($field_set);
        }

        // TODO: Register post types, etc.

        foreach ($this->hooks as $hook_class) {
            // TODO
            $hook = $container->get($hook_class);
            if ($hook instanceof Bootstrapable) {
                $hook->bootstrap();
            }
        }

        if ($this->router_class && !is_admin()) {
            $this->router = $container->get($this->router_class);
        }
    }

    public function registerSiteClass(string $class): self
    {
        assert(is_subclass_of($class, Site::class), 'Expected `Site` subclass');

        $this->site_class = $class;

        return $this;
    }

    public function registerNetworkClass(string $class): self
    {
        assert(is_subclass_of($class, Network::class), 'Expected `Network` subclass');

        $this->network_class = $class;

        return $this;
    }

    public function registerUserClass(string $class): self
    {
        assert(is_subclass_of($class, User::class), 'Expected `User` subclass');

        $this->user_class = $class;

        return $this;
    }

    public function registerModels(array $models): self
    {
        assert(every($models, 'is_subclass_of', [Model::class]), 'Expected a list of `Model` subclasses');

        $this->models = array_unique(array_merge($this->models, $models));

        return $this;
    }

    public function registerHooks(array $hooks): self
    {
        assert(every($hooks, 'class_exists'), 'Expected a list of existing classes');

        $this->hooks = array_unique(array_merge($this->hooks, $hooks));

        return $this;
    }

    public function registerTemplateEngine(string $class): self
    {
        assert(is_subclass_of($class, TemplateEngine::class), 'Expected `TemplateEngine` subclass');

        $this->template_engine_class = $class;

        return $this;
    }

    public function render(): string
    {
        // TODO: return $this->router->getController()->render();
        return '';
    }
}
