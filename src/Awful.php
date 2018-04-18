<?php
namespace Awful;

use Awful\Container\Container;
use Awful\Context\Context;
use Awful\Context\WordPressGlobals;
use Awful\Exceptions\UnknownBlockTypeException;
use Awful\Exceptions\UnregisteredBlockClassException;
use Awful\Models\Network;
use Awful\Models\Site;
use Awful\Models\User;
use Awful\Providers\Provider;
use Awful\Theme\Theme;
use WP_CLI;

final class Awful
{
    /**
     * @var string[]
     * @psalm-var array<string, class-string>
     */
    private static $blockClassMap = [];

    public static function bootstrap(array $providers): void
    {
        $GLOBALS['_awful_instance'] = new self($providers);
    }

    public static function registerBlockTypes(array $blockClassMap): void
    {
        assert(!array_intersect_key(self::$blockClassMap, $blockClassMap));
        assert(every($blockClassMap, 'class_exists'));

        self::$blockClassMap += $blockClassMap;
    }

    public static function blockClassForType(string $type): string
    {
        if (!isset(self::$blockClassMap[$type])) {
            throw new UnknownBlockTypeException($type);
        }

        return self::$blockClassMap[$type];
    }

    public static function blockTypeForClass(string $class): string
    {
        foreach (self::$blockClassMap as $type => $class) {
            if ($class === $type) {
                return $type;
            }
        }

        throw new UnregisteredBlockClassException($class);
    }

    /** @var Container */
    private $container;

    /** @var string[] */
    private $themes = [];

    /** @var string[] */
    private $plugins = [];

    /** @var string[] */
    private $commands = [];

    /**
     * @var string
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $userClass;

    /**
     * @var callable
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $setSiteCallback;

    /**
     * @var callable
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $setUserCallback;

    /**
     * @internal
     *
     * @param array $providers
     */
    private function __construct(array $providers)
    {
        assert((bool) $providers, 'Expected at least one provider');
        assert(every($providers, 'Awful\is_instanceof', [Provider::class]), 'Expected array of `ProviderInterface` instances');

        //
        // Initialize container.
        //

        $this->container = new Container();

        //
        // Run providers.
        //

        foreach ($providers as $provider) {
            $provider->register($this->container);
            $this->plugins = array_merge($this->plugins, $provider->plugins());
            $this->commands = array_merge($this->commands, $provider->commands());
            $this->themes += $provider->themes();
        }

        //
        // Initialize global context.
        //

        $this->container->register($GLOBALS['wpdb']);

        /**
         * `get_network()` will always return an object when `is_multisite()`.
         * @psalm-suppress PossiblyNullPropertyFetch
         */
        $network = null;
        // $network = is_multisite() ? new Network(get_network()->id) : null;


        $context = new Context($this, $network);
        $this->container->register($context);

        $this->container->register(new WordPressGlobals());

        //
        // Run bootstrap phases.
        //

        // Awful is run as a mu-plugin, so it's appropriate to run these here.
        $this->runPlugins();
        // add_action('after_setup_theme', [$this, 'setupTheme'], 2);
        // add_action('set_current_user', [$this, 'setUser'], 1);
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
                /** @psalm-suppress UndefinedClass */
                WP_CLI::add_command($command::commandName(), $this->container->get($command), $command::registrationArguments());
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
        if (did_action('set_current_user')) {
            $this->setUser();
        }

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
        $userClass = $this->userClass;
        if (!$userClass) {
            return;
        }
        ($this->setUserCallback)(new $userClass(get_current_user_id()));
        remove_action('set_current_user', [$this, 'setUser'], 1);
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
