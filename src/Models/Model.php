<?php
namespace Awful\Models;

use Awful\Models\Database\BlockSet;
use Awful\Models\Exceptions\FieldDoesNotExistException;
use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Fields\Field;
use stdClass;

abstract class Model
{
    /**
     * @var Field[][]
     * @psalm-var array<string, array<string, Field>>
     */
    private static $allFields = [];

    /**
     * The set of fields for this class, memoized.
     *
     * @return Field[]
     * @psalm-return array<string, Field>
     */
    final public static function fields(): array
    {
        if (!isset(self::$allFields[static::class])) {
            self::$allFields[static::class] = static::registerFields();
        }
        return self::$allFields[static::class];
    }

    /**
     * The set of fields for this class.
     *
     * @return Field[]
     * @psalm-return array<string, Field>
     */
    protected static function registerFields(): array
    {
        return [];
    }

    /** @var stdClass|null */
    private $block;

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

        $field = static::fields()[$key] ?? null;
        if (!$field) {
            throw new FieldDoesNotExistException("There is no field '$key' on " . static::class);
        }
        $value = $this->getRaw($key);
        return $this->formattedDataCache[$key] = $field->toPhp($value, $this, $key);
    }

    /**
     * Gets a raw field value.
     *
     * @param  string $key
     * @return mixed
     */
    final public function getRaw(string $key)
    {
        if ($this->block === null) {
            $this->block = $this->fetchBlockRecord();
        }

        return $this->block->data[$key] ?? null;
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
        if ($this->block === null) {
            $this->block = $this->fetchBlockRecord();
        }

        // TODO: Consider validation at this point.
        $this->blockSet()->set($this->block->uuid, $data + $this->block->data);

        // Clear the formatted data cache for the newly set fields.
        foreach ($data as $key => $_) {
            if (isset($this->formattedDataCache[$key])) {
                unset($this->formattedDataCache[$key]);
            }
        }

        return $this;
    }

    /**
     * @return (string[])[]|null
     * @psalm-return null|array<string, array<int, string>>
     */
    final public function cleanFields(): ?array
    {
        if ($this->block === null) {
            $this->block = $this->fetchBlockRecord();
        }

        // Clean fields.
        $data = $this->block->data;
        $cleanedData = [];
        $errors = [];
        foreach (static::fields() as $key => $field) {
            $value = $data[$key] ?? null;

            if ($value === null && $field->isRequired()) {
                $errors[$key] = ["Field '$key' is required."];
                continue;
            }

            try {
                $cleanedData[$key] = $field->clean($value, $this);
            } catch (ValidationException $e) {
                $errors[$key] = [$e->getMessage()];
            }
        }

        if ($errors) {
            // Don't overwrite data if there were validation errors; instead,
            // return those so the consumer can act on them.  (Presumably they
            // will make their way to the client).
            return $errors;
        }

        // Completely override the block with the cleaned data, removing any
        // non-existent fields.
        $this->blockSet()->set($this->block->uuid, $cleanedData);
        // Clear the entire cache.
        $this->formattedDataCache = [];

        return null; // Indicates success
    }

    public function clean(): void
    {
    }

    public function reloadBlocks(): void
    {
        $this->block = null;
        $this->formattedDataCache = [];
    }

    abstract public function blockSet(): BlockSet;

    /**
     * The id of this object.
     *
     * @return int
     */
    abstract public function id(): int;

    /**
     * Determines if this model actually exists in the database.
     *
     * @return bool True if there is a row in the database which represents this
     *              instance, otherwise false.
     */
    abstract public function exists(): bool;

    abstract protected function fetchBlockRecord(): stdClass;
}
