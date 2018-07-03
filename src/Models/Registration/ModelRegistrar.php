<?php
namespace Awful\Models\Registration;

use Awful\Container\Container;
use Awful\Models\GenericPost;
use Closure;

/**
 * Utility class.
 */
class ModelRegistrar
{
    /** @var Container */
    private $container;

    /** @var string[] */
    private $deferred = [];

    /**
     * Whether the WordPress init action has run.
     *
     * @var bool
     */
    private $hasInit;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->hasInit = (bool) did_action('init');

        if ($this->hasInit) {
            return;
        }

        add_action('init', function (): void {
            $this->hasInit = true;
            foreach ($this->deferred as $postClass) {
                $this->registerPostType($postClass);
            }
        });
    }

    /**
     * Registers a post type with WordPress, given an Awful model class.
     *
     * @param string $postClass Name of a subclass of `Awful\GenericPost`.
     *
     * @return void
     */
    public function registerPostType(string $postClass): void
    {
        assert(is_subclass_of($postClass, GenericPost::class), 'Expected `GenericPost` subclass');

        if ($postClass::IS_BUILTIN) {
            return;
        }

        if (!$this->hasInit) {
            $this->deferred[] = $postClass;
            return;
        }

        $settings = $postClass::settings();
        if ($settings instanceof Closure) {
            $settings = $this->container->call($settings);
        }
        assert(is_array($settings), 'Expected array of post type settings.');

        register_post_type($postClass::TYPE, $settings);
    }
}
