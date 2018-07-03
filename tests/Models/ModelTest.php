<?php
namespace Awful\Models;

use Awful\AwfulTestCase;
use Awful\Models\Database\BlockSet;
use Awful\Models\Database\Map\BlockTypeMap;
use Awful\Models\Exceptions\FieldDoesNotExistException;
use Awful\Models\Fields\Field;
use Awful\Models\ModelTest\MockModel;
use function Awful\uuid;

class ModelTest extends AwfulTestCase
{
    public function testCleanFieldsWithInvalidField()
    {
        $model = $this->mockModel([
            'required' => 'invalid',
        ]);

        $errors = $model->cleanFields();
        $this->assertSame(1, count($errors), 'Optional field is allow');
        $this->assertTrue(!empty($errors['required']));
        $this->assertSame(['invalid message'], $errors['required']);
    }

    public function testCleanFieldsWithMissingRequiredField()
    {
        $model = $this->mockModel([]);

        $errors = $model->cleanFields();
        $this->assertSame(1, count($errors), 'Optional field is allow');
        $this->assertTrue(!empty($errors['required']));
        $this->assertSame(1, count($errors['required']));
        $this->assertTrue(strpos($errors['required'][0], 'is required') !== false);
    }

    public function testCleanFieldsWithValidFields()
    {
        $model = $this->mockModel([
            'optional' => 'raw',
            'required' => 'bar',
        ]);

        $optionalBefore = $model->get('optional');
        $this->assertSame('raw', $model->getRaw('optional'));

        $this->assertSame(null, $model->cleanFields(), 'no errors');

        $this->assertSame('clean', $model->getRaw('optional'), 'cleaned value is set');
        $this->assertNotSame($optionalBefore, $model->get('optional'), 'cleanFields should reset formatted data cache');
    }

    public function testGet()
    {
        $model = $this->mockModel([
            'optional' => 'clean',
            'required' => 'required value',
        ]);

        $this->assertSame('required value', $model->get('required'));

        $optional = $model->get('optional');
        $this->assertTrue(is_object($optional) && $optional->test, 'Field::toPhp() is called correctly');
        $this->assertSame($optional, $model->get('optional'), 'formatted data cache works');

        $this->expectException(FieldDoesNotExistException::class);
        $model->get('non-existent');
    }

    public function testGetRaw()
    {
        $model = $this->mockModel([
            'foo' => 'bar',
            'fiz' => ['buz'],
        ]);

        $this->assertSame('bar', $model->getRaw('foo'));
        $this->assertSame(['buz'], $model->getRaw('fiz'));
        $this->assertSame(null, $model->getRaw('non-existent'));
    }

    public function testReloadBlocks()
    {
        $model = $this->mockModel([
            'optional' => 'clean',
        ]);

        $optionalBefore = $model->get('optional');
        $this->assertSame(1, $model->fetchBlockRecordCallCount);
        $this->assertTrue(is_object($optionalBefore) && $optionalBefore->test);

        $model->reloadBlocks();

        $optionalAfter = $model->get('optional');
        $this->assertSame(2, $model->fetchBlockRecordCallCount);
        $this->assertTrue(is_object($optionalAfter) && $optionalAfter->test);
        $this->assertNotSame($optionalBefore, $optionalAfter);
    }

    public function testSet()
    {
        $model = $this->mockModel([
            'required' => 'required value',
            'optional' => 'clean',
        ]);

        $blockRecord = $model->blockSet()->get($model->uuid);

        $optional = $model->get('optional');
        $this->assertTrue(is_object($optional) && $optional->test);

        $this->assertSame('required value', $blockRecord->data['required']);
        $this->assertSame('required value', $model->getRaw('required'));
        $this->assertSame('required value', $model->get('required'));

        $model->set(['required' => 'something else']);

        $this->assertSame('something else', $blockRecord->data['required']);
        $this->assertSame('something else', $model->getRaw('required'));
        $this->assertSame('something else', $model->get('required'));

        $this->assertSame($optional, $model->get('optional'), 'Other values untouched by set');
    }

    public function testStaticFieldsMethod()
    {
        $fields = MockModel::fields();
        $this->assertSame(2, count($fields));
        $this->assertContainsOnlyInstancesOf(Field::class, $fields);

        $this->assertSame(MockModel::fields(), MockModel::fields(), 'The call was memoized');
        $this->assertSame(1, MockModel::$registerFieldsCallCount, 'The call was memoized: registerFields was only called once');
    }

    private function mockModel(array $data): MockModel
    {
        $uuid = uuid();

        $blockSet = new BlockSet(
            new BlockTypeMap([]),
            $this->createMock(WordPressModel::class),
            [
                $uuid => (object) [
                    'uuid' => $uuid,
                    'data' => $data,
                ],
            ]
        );

        // Return a subclass so the fields are not memoized.
        return new class($blockSet, $uuid) extends MockModel {
        };
    }
}
namespace Awful\Models\ModelTest;

use Awful\Models\Database\BlockSet;
use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Fields\Field;
use Awful\Models\Model;

class MockModel extends Model
{
    /** @var int */
    public static $registerFieldsCallCount = 0;

    protected static function registerFields(): array
    {
        self::$registerFieldsCallCount++;

        return [
            'optional' => new class(['required' => false]) extends Field {
                public function toPhp($value, Model $model, string $fieldKey)
                {
                    // Return an object so its identity can be checked.
                    return (object) ['test' => true];
                }

                public function clean($value, Model $model)
                {
                    return $value === 'raw' ? 'clean' : 'unclean';
                }
            },
            'required' => new class(['required' => true]) extends Field {
                public function toPhp($value, Model $model, string $fieldKey)
                {
                    return $value;
                }

                public function clean($value, Model $model)
                {
                    if ($value === 'invalid') {
                        throw new ValidationException('invalid message');
                    }
                    return $value;
                }
            },

        ];
    }

    /** @var BlockSet */
    public $blockSet;

    /** @var int */
    public $fetchBlockRecordCallCount = 0;

    /** @var string */
    public $uuid;

    public function __construct(BlockSet $blockSet, string $uuid)
    {
        $this->blockSet = $blockSet;
        $this->uuid = $uuid;
    }

    public function blockSet(): BlockSet
    {
        return $this->blockSet;
    }

    public function exists(): bool
    {
        return true;
    }

    public function id(): int
    {
        return 0;
    }

    protected function fetchBlockRecord(): \stdClass
    {
        $this->fetchBlockRecordCallCount++;
        return $this->blockSet->get($this->uuid);
    }
}
