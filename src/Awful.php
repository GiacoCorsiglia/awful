<?php
namespace Awful;

use Awful\Cli\AwfulCommand;
use Awful\Container\Container;
use Awful\Context\Context;
use Awful\Context\WordPressGlobals;
use Awful\Models\Database\BlockSetManager;
use Awful\Models\Database\Database;
use Awful\Models\Database\EntityManager;
use Awful\Models\Database\Map\BlockTypeMap;
use Awful\Models\Database\Map\PostTypeMap;
use Awful\Models\Network;
use Awful\Models\Registration\FieldHooks;
use Awful\Models\Registration\ModelRegistrar;
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
     * @param array $blockTypeMap
     * @psalm-param array<class-string, array<int, string>|string> $blockTypeMap
     *
     * @return void
     */
    public static function bootstrap(array $providers, array $blockTypeMap): void
    {
        if (!defined('AWFUL_ENV')) {
            define('AWFUL_ENV', 'production');
        }
        $GLOBALS['_awful_instance'] = new self($providers, $blockTypeMap);
    }

    /**
     * @var string[]
     * @psalm-var array<int, string>
     */
    private $commands = [];

    /** @var Container */
    private $container;

    /** @var EntityManager */
    private $entityManager;

    /**
     * @var string[]
     * @psalm-var array<int, string>
     */
    private $plugins = [];

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
     * @var string[]
     * @psalm-var array<string, class-string>
     */
    private $themes = [];

    /**
     * @var string
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $userClass;

    /**
     * @internal
     *
     * @param array $providers
     * @psalm-param array<int, Provider> $providers
     * @param array $blockTypeMap
     * @psalm-param array<class-string, array<int, string>|string> $blockTypeMap
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

        $blockTypeMapInstance = new BlockTypeMap($blockTypeMap);
        $this->container->register($blockTypeMapInstance);

        $blockSetManager = new BlockSetManager($database, $blockTypeMapInstance);
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
        if (!defined('AWFUL_INSTALLED') || !AWFUL_INSTALLED) {
            // Just register the Awful CLI and bail to avoid DB errors.
            if (defined('WP_CLI') && WP_CLI) {
                /** @psalm-suppress UndefinedClass */
                WP_CLI::add_command(AwfulCommand::commandName(), $this->container->get(AwfulCommand::class), AwfulCommand::registrationArguments());
            }
            return;
        }

        // Awful is run as a mu-plugin, so it's appropriate to run these here.
        $this->runPlugins();
        add_action('after_setup_theme', [$this, 'setupTheme'], 2);
        add_action('set_current_user', [$this, 'setUser'], 1);
    }

    /**
     * @internal Exposed to be called by Context.
     *
     * @param callable $setSite
     * @param callable $setUser
     *
     * @return void
     */
    public function registerContextCallbacks(
        callable $setSite,
        callable $setUser
    ): void {
        $this->setSiteCallback = $setSite;
        $this->setUserCallback = $setUser;
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

        $modelRegistrar = new ModelRegistrar($this->container);

        $postTypes = $theme->postTypes();
        $map = [];
        foreach ($postTypes as $postTypeName => $postTypeClass) {
            $map[$postTypeClass] = $postTypeName;
            $modelRegistrar->registerPostType($postTypeClass);
        }
        $this->container->register($postTypeMap = new PostTypeMap($map));
        $this->container->get(FieldHooks::class);

        foreach ($theme->hooks() as $hook) {
            $this->container->get($hook);
        }
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
}
