<?php
namespace Awful\Models\Fields;

use Awful\AwfulTestCase;
use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Model;

class IntegerFieldTest extends AwfulTestCase
{
    /** @var IntegerField */
    private $field;

    /** @var Model */
    private $model;

    public function setUp()
    {
        parent::setUp();

        $this->field = new IntegerField();
        $this->model = $this->getMockForAbstractClass(Model::class);
    }

    public function testCleanRejectsAboveMax()
    {
        $field = new IntegerField([
            'max' => 5,
        ]);

        $this->expectException(ValidationException::class);
        $field->clean(8, $this->model);
    }

    public function testCleanRejectsBelowMin()
    {
        $field = new IntegerField([
            'min' => -5,
        ]);

        $this->expectException(ValidationException::class);
        $field->clean(-8, $this->model);
    }

    public function testCleanRejectsFloat()
    {
        $this->expectException(ValidationException::class);
        $this->field->clean(12.5, $this->model);
    }

    public function testCleanRejectsNumericString()
    {
        $this->expectException(ValidationException::class);
        $this->field->clean('12', $this->model);
    }

    public function testCleanValid()
    {
        $this->assertSame(null, $this->field->clean(null, $this->model));
        $this->assertSame(5, $this->field->clean(5, $this->model));
        $this->assertSame(-5, $this->field->clean(-5, $this->model));
    }

    public function testToPhp()
    {
        $this->assertSame(null, $this->field->toPhp(null, $this->model, ''));
        $this->assertSame(null, $this->field->toPhp('', $this->model, ''));
        $this->assertSame(null, $this->field->toPhp([], $this->model, ''));
        $this->assertSame(null, $this->field->toPhp(['foo'], $this->model, ''));
        $this->assertSame(null, $this->field->toPhp(true, $this->model, ''));
        $this->assertSame(null, $this->field->toPhp(false, $this->model, ''));
        $this->assertSame(5, $this->field->toPhp(5, $this->model, ''));
        $this->assertSame(5, $this->field->toPhp('5', $this->model, ''));
        $this->assertSame(5, $this->field->toPhp(5.5, $this->model, ''));
        $this->assertSame(-5, $this->field->toPhp(-5, $this->model, ''));
    }
}
