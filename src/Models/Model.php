<?php
namespace Awful\Models;

use Awful\Models\Fields\FieldsResolver;

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

    protected const WORDPRESS_OBJECT_FIELDS = [];

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
     * @param int            $id
     * @param int            $site_id
     * @param FieldsResolver $resolver
     *
     * @return static
     */
    final public static function id(
        int $id,
        int $site_id = 0,
        FieldsResolver $resolver = null
    ): self {
        $object_type = static::OBJECT_TYPE;

        if (isset(self::$instances[$object_type][$id])) {
            return self::$instances[$object_type][$id];
        }

        if (!isset(self::$instances[$object_type])) {
            self::$instances[$object_type] = [];
        }

        $class = static::getClassForId($id);
        return self::$instances[$object_type][$id] = new $class($id, $site_id, $resolver);
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

    /**
     * The primary key of this object in the database.
     *
     * @var int
     */
    protected $id;

    /**
     * Gets the primary key of this object in the database.
     *
     * An unsaved object may have an ID of 0, but may also have a positive ID
     *
     * @return int The primary key.
     */
    final public function getId(): int
    {
        return $this->id;
    }

    final public function getDataSource(): HasFields
    {
        return $this;
    }

    final public function getDataPrefix(): string
    {
        return '';
    }

    public function getRawFieldValue(string $key)
    {
        if ($this->data === null) {
            $this->fetchData();
        }

        $value = $this->data[$key] ?? null;

        if ($value) {
            // TODO: Consider caching this.  On the other hand, the filtered
            // value will already be cached.
            $value = maybe_unserialize($value);
        }

        return $value;
    }

    /**
     * Determines if this model actually exists in the database.
     *
     * @return bool True if there is a row in the database which represents this
     *              instance, otherwise false.
     */
    abstract public function exists(): bool;

    /**
     * Initializes `$this->data` from the database.
     *
     * Only to be called once in the life-cycle of an instance.
     *
     * @return void
     */
    abstract protected function fetchData();
}
