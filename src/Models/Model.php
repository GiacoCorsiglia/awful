<?php
namespace Awful\Models;

use Awful\Models\Database\BlockSet;
use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Fields\Field;
use stdClass;

abstract class Model
{
    /** @var Field[][] */
    private static $allFields = [];

    /**
     * The set of fields for this class.
     *
     * @return Field[]
     * @psalm-return array<string, Field>
     */
    public static function fields(): array
    {
        return [];
    }

    private static function field(string $key): ?Field
    {
        if (!isset(self::$allFields[static::class])) {
            self::$allFields[static::class] = static::fields();
        }
        return self::$allFields[static::class][$key] ?? null;
    }

    /** @var BlockSet */
    private $blockSet;

    /** @var stdClass */
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

        $field = static::field($key);
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
            $this->block = $this->fetchBlockRecord($this->blockSet);
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
        // TODO: Consider validation at this point.
        $this->blockSet->set($this->block->uuid, $data + $this->block->data);

        // Clear the formatted data cache for the newly set fields.
        foreach ($data as $key => $_) {
            if (isset($this->formattedDataCache[$key])) {
                unset($this->formattedDataCache[$key]);
            }
        }

        return $this;
    }

    final public function blockSet(): BlockSet
    {
        return $this->blockSet;
    }

    public function clean(): array
    {
        $cleanedData = [];
        foreach (static::fields() as $key => $field) {
            $value = $this->block->data[$key] ?? null;

            if ($value === null && $field->isRequired()) {
                throw new ValidationException("Field '$key' is required.");
            }

            $cleanedData[$key] = $field->clean($value, $this);
        }

        return $cleanedData;
    }

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

    protected function initializeBlockSet(BlockSet $blockSet): void
    {
        assert($this->blockSet === null, 'Cannot initialize blockSet more than once');
        $this->blockSet = $blockSet;
    }

    abstract protected function fetchBlockRecord(BlockSet $blockSet): stdClass;
}
