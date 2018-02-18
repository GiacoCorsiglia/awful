<?php
namespace Awful\Models;

use Awful\Container\Container;

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
    private $has_init = false;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        add_action('init', function (): void {
            $this->has_init = true;
            foreach ($this->deferred as $post_subclass) {
                $this->registerPostType($post_subclass);
            }
        });
    }

    /**
     * Registers a post type with WordPress, given an Awful model class.
     *
     * @param  string $post_subclass Name of a subclass of `Awful\GenericPost`.
     * @return void
     */
    public function registerPostType(string $post_subclass): void
    {
        assert(is_subclass_of($post_subclass, GenericPost::class), 'Expected `GenericPost` subclass');

        if (!$this->has_init) {
            $this->deferred[] = $post_subclass;
            return;
        }

        $settings = $post_subclass::getSettings();
        if (is_callable($settings)) {
            $settings = $this->container->call($settings);
        }
        assert(is_array($settings), 'Expected array of post type settings.');

        \register_post_type($post_subclass::TYPE, $settings);
    }
}
