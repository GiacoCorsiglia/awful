<?php
namespace Awful\Models;

use Awful\Context\Context;
use Awful\Models\Database\BlockSet;
use Awful\Models\Fields\Field;
use stdClass;

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
     * The set of fields for this class.
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
        if ($this->block === null) {
            $this->block = $this->fetchBlock($this->blockSet);
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
        foreach (static::fields(self::$context) as $key => $field) {
            $cleanedData[$key] = $field->clean($this->block->data[$key] ?? null);
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

    abstract protected function fetchBlock(BlockSet $blockSet): stdClass;
}
