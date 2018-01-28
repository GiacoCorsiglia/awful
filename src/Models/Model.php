<?php
namespace Awful\Models;

use Awful\Fields\FieldsResolver;
use Awful\Fields\HasFields;

/**
 * Base class for a objects which represent actual database rows.
 */
abstract class Model extends HasFields
{
    /**
     * One of 'post', 'user', 'comment, 'term', 'site', or 'network'.
     * @var string
     */
    protected const OBJECT_TYPE = '';

    /**
     * Cache of instances per object type per id.
     * @var (self[])[]
     */
    private static $instances = [];

    /**
     * Contains per-request initialization code to set up this content type,
     * such as registration of a custom post type with WordPress.
     *
     * Return a callable if it needs to be invoked with dependency injection.
     *
     * @return callable|null
     */
    public static function bootstrap(): ?callable
    {
        // Implemented here so it's safe to call no matter what.
    }

    /**
     * Factory function to get an instance of this model with the given ID.
     *
     * Will return always the same instance for the same id.
     *
     * @param int $id
     * @param int $site_id
     *
     * @return static
     */
    final public static function id(int $id, int $site_id = 0): self
    {
        $object_type = static::OBJECT_TYPE;

        if (isset(self::$instances[$object_type][$id])) {
            return self::$instances[$object_type][$id];
        }

        if (!isset(self::$instances[$object_type])) {
            self::$instances[$object_type] = [];
        }

        $class = static::getClassForId($id);
        return self::$instances[$object_type][$id] = new $class($id);
    }

    final public static function create(): self
    {
        return new static(0);
    }

    /**
     * Allows subclasses to return the correct class to be instantiated for an
     * object with the given ID.
     *
     * @param int $id
     *
     * @return string
     */
    protected static function getClassForId(int $id): string
    {
        return static::class;
    }

    /** @var int */
    protected $id;

    /** @var int */
    private $site_id;

    protected function __construct(
        int $id = 0,
        int $site_id = 0,
        FieldsResolver $resolver
    ) {
        $this->id = $id;
        $this->site_id = is_multisite() ? $site_id : 0;

        $this->setFieldsResolver($resolver);
    }

    final public function getId(): int
    {
        return $this->id;
    }

    abstract public function isSaved(): bool;

    public function getRaw(string $key)
    {
        if ($this->data === null) {
            $this->fetchData();
        }

        $value = $this->data[$key] ?? null;

        if ($value) {
            // TODO: Consider caching this.
            $value = maybe_unserialize($value);
        }

        return $value;
    }

    /**
     * Initializes `$this->data` from the database.
     *
     * Only to be called once in the life-cycle of an instance.
     *
     * @return void
     */
    abstract protected function fetchData();

    /**
     * Calls the given function in the context of the owner site ID set for this
     * instance, passing along the remaining args and returning the result.
     *
     * @param callable $callable Function to invoke.
     * @param mixed    ...$args  Positional arguments to pass to $callable.
     *
     * @return mixed Return value of invoked function.
     */
    final protected function callInSiteContext(callable $callable, ...$args)
    {
        if ($this->site_id) {
            switch_to_blog($this->site_id);
        }

        $ret = $callable(...$args);

        if ($this->site_id) {
            restore_current_blog();
        }

        return $ret;
    }
}
