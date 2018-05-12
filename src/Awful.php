<?php
namespace Awful;

use Awful\Cli\AwfulCommand;
use Awful\Container\Container;
use Awful\Context\Context;
use Awful\Context\WordPressGlobals;
use Awful\Models\Database\BlockSetManager;
use Awful\Models\Database\BlockTypeMap;
use Awful\Models\Database\Database;
use Awful\Models\Database\EntityManager;
use Awful\Models\Network;
use Awful\Models\Site;
use Awful\Models\User;
use Awful\Providers\Provider;
use Awful\Theme\Theme;
use WP_CLI;

final class Awful
{
    /**
     * @param Provider[] $providers
     * @psalm-param array<int, Provider> $providers
     * @param array $blockClassMap
     * @psalm-param array<class-string, string[]|string> $blockClassMap
     * @return void
     */
    public static function bootstrap(array $providers, array $blockClassMap): void
    {
        if (!defined('AWFUL_ENV')) {
            define('AWFUL_ENV', 'production');
        }
        $GLOBALS['_awful_instance'] = new self($providers, $blockClassMap);
    }

    /** @var Container */
    private $container;

    /** @var string[] */
    private $themes = [];

    /** @var string[] */
    private $plugins = [];

    /** @var string[] */
    private $commands = [];

    /** @var EntityManager */
    private $entityManager;

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
     * @param array $blockTypeMap
     */
    private function __construct(array $providers, array $blockTypeMap)
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

        $database = new Database($GLOBALS['wpdb']);
        $this->container->register($database);

        $blockSetManager = new BlockSetManager($database, new BlockTypeMap($blockTypeMap));
        $this->container->register($blockSetManager);

        $this->entityManager = new EntityManager($blockSetManager);
        $this->container->register($this->entityManager);

        /**
         * `get_network()` will always return an object when `is_multisite()`.
         * @psalm-suppress PossiblyNullPropertyFetch
         */
        $network = new Network(is_multisite() ? get_network()->id : 0);

        $context = new Context($this, $network);
        $this->container->register($context);

        $this->container->register(new WordPressGlobals());

        //
        // Run bootstrap phases.
        //

        /** @psalm-suppress UndefinedConstant */
        if (AWFUL_ENV === 'dev' && getenv('AWFUL_INSTALLING') === 'yes' && defined('WP_CLI') && WP_CLI) {
            // Just register the Awful CLI and bail to avoid DB errors.
            /** @psalm-suppress UndefinedClass */
            WP_CLI::add_command(AwfulCommand::commandName(), $this->container->get(AwfulCommand::class), AwfulCommand::registrationArguments());
            return;
        }

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
        $siteId = is_multisite() ? get_current_blog_id() : 0;
        ($this->setSiteCallback)(new $siteClass($this->entityManager, $siteId));

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
        $userId = get_current_user_id();
        ($this->setUserCallback)(new $userClass($this->entityManager, $userId));
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
