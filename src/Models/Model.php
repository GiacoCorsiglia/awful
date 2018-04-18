<?php
namespace Awful\Models;

use Awful\Context\Context;
use Awful\Models\Fields\Field;

abstract class Model
{
    /** @var Context */
    private static $context;

    /** @var Field[][] */
    private static $allFields = [];

    final public static function initializeContext(Context $context): void
    {
        self::$context = $context;
        // Reset the set of all fields if context changes (for testing).
        self::$allFields = [];
    }

    /**
     * Undocumented function.
     *
     * @param  Context $context
     * @return Field[]
     * @psalm-return array<string, Field>
     */
    public static function fields(Context $context): array
    {
        return [];
    }

    private static function field(string $key): Field
    {
        if (!isset(self::$allFields[static::class])) {
            self::$allFields[static::class] = static::fields(self::$context);
        }
        return self::$allFields[static::class][$key];
    }

    /** @var array */
    private $data;

    /** @var array */
    private $modifiedData = [];

    /** @var array */
    private $formattedDataCache = [];

    /**
     * Gets a field value.
     *
     * @param  string $key
     * @return mixed
     */
    final public function get(string $key)
    {
        if (isset($this->formattedDataCache[$key])) {
            return $this->formattedDataCache[$key];
        }

        $value = $this->getRaw($key);
        $field = static::field($key);
        return $this->formattedDataCache[$key] = $field->forPhp($value, $this, $key);
    }

    /**
     * Gets a raw field value.
     *
     * @param  string $key
     * @return mixed
     */
    final public function getRaw(string $key)
    {
        if ($this->modifiedData && array_key_exists($key, $this->modifiedData)) {
            // Have to use `array_key_exists()` to catch values that were set
            // to `null`.
            return $this->modifiedData[$key];
        }
        return $this->data[$key] ?? null;
    }

    /**
     * Sets raw field values.
     *
     * @param array $data
     * @psalm-param array<string, mixed> $data
     * @return $this
     */
    final public function set(array $data): self
    {
        // TODO: Consider validation at this point.
        $this->modifiedData = $data + $this->modifiedData;
        if (!$this->formattedDataCache) {
            // Don't bother clearing the cache if it's totally empty.
            return $this;
        }
        // Clear the formatted data cache.
        foreach ($data as $key => $_) {
            if (isset($this->formattedDataCache[$key])) {
                unset($this->formattedDataCache[$key]);
            }
        }
        return $this;
    }

    final public function isModified(): bool
    {
        return (bool) $this->modifiedData;
    }

    public function clean(): array
    {
        $fields = static::fields(self::$context);
        $data = $this->modifiedData + $this->data;

        $cleanedData = [];
        foreach ($fields as $key => $field) {
            $cleanedData[$key] = $field->clean($data[$key] ?? null);
        }

        return $cleanedData;
    }

    /**
     * Determines if this model actually exists in the database.
     *
     * @return bool True if there is a row in the database which represents this
     *              instance, otherwise false.
     */
    abstract public function exists(): bool;

    protected function initializeData(array $data): void
    {
        assert($this->data === null, 'Cannot initialize data more than once');
        $this->data = $data;
    }
}
