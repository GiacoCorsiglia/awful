<?php
namespace Awful\Models\Fields;

use Awful\AwfulTestCase;
use Awful\Models\Model;

class DecimalFieldTest extends AwfulTestCase
{
    /** @var DecimalField */
    private $field;

    /** @var Model */
    private $model;

    public function setUp()
    {
        parent::setUp();

        $this->field = new DecimalField();
        $this->model = $this->getMockForAbstractClass(Model::class);
    }

    public function testCleanRejectsAboveMax()
    {
        $field = new DecimalField([
            'max' => 5.5,
        ]);

        $this->expectValidationException();
        $field->clean(8.5, $this->model);
    }

    public function testCleanRejectsBelowMin()
    {
        $field = new DecimalField([
            'min' => -5.5,
        ]);

        $this->expectValidationException();
        $field->clean(-8.5, $this->model);
    }

    public function testCleanRejectsNumericString()
    {
        $this->expectValidationException();
        $this->field->clean('12', $this->model);
    }

    public function testCleanValid()
    {
        $this->assertSame(null, $this->field->clean(null, $this->model));
        $this->assertSame(5.5, $this->field->clean(5.5, $this->model));
        $this->assertSame(-5.5, $this->field->clean(-5.5, $this->model));
        $this->assertSame(5.0, $this->field->clean(5, $this->model), 'Accepts positive integer');
        $this->assertSame(-5.0, $this->field->clean(-5, $this->model), 'Accepts negative integer');
    }

    public function testToPhp()
    {
        $this->assertSame(null, $this->field->toPhp(null, $this->model, ''));
        $this->assertSame(null, $this->field->toPhp('', $this->model, ''));
        $this->assertSame(null, $this->field->toPhp([], $this->model, ''));
        $this->assertSame(null, $this->field->toPhp(['foo'], $this->model, ''));
        $this->assertSame(null, $this->field->toPhp(true, $this->model, ''));
        $this->assertSame(null, $this->field->toPhp(false, $this->model, ''));
        $this->assertSame(5.0, $this->field->toPhp(5, $this->model, ''));
        $this->assertSame(5.0, $this->field->toPhp('5', $this->model, ''));
        $this->assertSame(-5.0, $this->field->toPhp(-5, $this->model, ''));
        $this->assertSame(5.5, $this->field->toPhp(5.5, $this->model, ''));
        $this->assertSame(5.5, $this->field->toPhp('5.5', $this->model, ''));
        $this->assertSame(-5.5, $this->field->toPhp(-5.5, $this->model, ''));
    }
}
