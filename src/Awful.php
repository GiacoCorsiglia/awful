<?php
namespace Awful;

use Awful\Container\Container;
use Awful\Context\Context;
use Awful\Context\WordPressGlobals;
use Awful\Models\Network;
use Awful\Models\Site;
use Awful\Models\User;
use Awful\Providers\ProviderInterface;

final class Awful
{
    public static function bootstrap(array $providers)
    {
        $GLOBALS['_awful_instance'] = new self($providers);
    }

    /** @var \Container\Container */
    private $container;

    /** @var Context */
    private $context;

    /** @var string[] */
    private $themes = [];

    /** @var string[] */
    private $plugins = [];

    /** @var string[] */
    private $commands = [];

    /** @var string */
    private $userClass;

    /** @var callable */
    private $setSiteCallback;

    /** @var callable */
    private $setUserCallback;

    /**
     * @internal
     *
     * @param array $providers
     */
    private function __construct(array $providers)
    {
        assert((bool) $providers, 'Expected at least one provider');
        assert(every($providers, 'is_implementation', [ProviderInterface::class]), 'Expected array of `ProviderInterface` instances');

        //
        // Initialize container.
        //

        $this->container = new Container();

        //
        // Run providers.
        //

        foreach ($providers as $provider) {
            $provider->register($this->container);
            array_push($this->plugins, ...$provider->plugins());
            array_push($this->commands, ...$provider->commands());
            $this->themes += $provider->themes();
        }

        //
        // Initialize global context.
        //

        $network = is_multisite() ? new Network(get_network()) : null;

        $this->context = new Context($this, $network);
        $this->container->register($this->context);

        $this->container->register(new WordPressGlobals());

        //
        // Run bootstrap phases.
        //

        // Awful is run as a mu-plugin, so it's appropriate to run these here.
        $this->runPlugins();
        add_action('after_setup_theme', [$this, 'setupTheme'], 2);
        add_action('set_current_user', [$this, 'setUser'], 1);
    }

    /**
     * @return void
     */
    private function runPlugins(): void
    {
        foreach ($this->plugins as $plugin) {
            $this->container->get($plugin);
        }

        if (defined('WP_CLI') && WP_CLI) {
            foreach ($this->commands as $command) {
                $command::register($this->container->get($command));
            }
        }
    }

    /**
     * @internal Exposed as an action.
     *
     * @return void
     */
    public function setupTheme(): void
    {
        $class = $this->themes[get_stylesheet()] ?? '';
        /** @var \Awful\Theme\Theme */
        $theme = $class
            ? new $class()
            : new class() extends Theme {
                // Fallback implementation just in case.
            };

        $siteClass = $theme->siteClass() ?: Site::class;
        ($this->setSiteCallback)(new $siteClass(get_current_blog_id() ?: 0));

        $this->userClass = $theme->userClass() ?: User::class;

        foreach ($theme->hooks() as $hook) {
            $this->container->get($hook);
        }

        $models = $theme->models();
    }

    /**
     * @internal Exposed as an action.
     *
     * @return void
     */
    public function setUser(): void
    {
        ($this->setUserCallback)(new $userClass(get_current_user_id()));
    }

    /**
     * @internal Exposed to be called by Context.
     *
     * @param  callable $setSite
     * @param  callable $setUser
     * @return void
     */
    public function registerContextCallbacks(
        callable $setSite,
        callable $setUser
    ): void {
        $this->setSiteCallback = $setSite;
        $this->setUserCallback = $setUser;
    }
}
