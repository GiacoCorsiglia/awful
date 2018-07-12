<?php
namespace Awful\Models\Fields;

use Awful\AwfulTestCase;
use Awful\Models\Model;

class BooleanFieldTest extends AwfulTestCase
{
    /** @var TextField */
    private $field;

    /** @var Model */
    private $model;

    public function setUp()
    {
        parent::setUp();

        $this->field = new BooleanField();
        $this->model = $this->getMockForAbstractClass(Model::class);
    }

    public function testCleanAcceptsBoolOrNull()
    {
        $this->assertSame(null, $this->field->clean(null, $this->model));
        $this->assertSame(false, $this->field->clean(false, $this->model));
        $this->assertSame(true, $this->field->clean(true, $this->model));
    }

    public function testCleanRejectsString()
    {
        $this->expectValidationException();
        $this->field->clean('', $this->model);
    }

    public function testToPhp()
    {
        $this->assertSame(false, $this->field->toPhp(false, $this->model, ''));
        $this->assertSame(false, $this->field->toPhp(null, $this->model, ''));
        $this->assertSame(false, $this->field->toPhp(0, $this->model, ''));
        $this->assertSame(false, $this->field->toPhp('', $this->model, ''));
        $this->assertSame(false, $this->field->toPhp([], $this->model, ''));

        $this->assertSame(true, $this->field->toPhp(true, $this->model, ''));
        $this->assertSame(true, $this->field->toPhp(1, $this->model, ''));
        $this->assertSame(true, $this->field->toPhp('foo', $this->model, ''));
        $this->assertSame(true, $this->field->toPhp(['foo'], $this->model, ''));
        $this->assertSame(true, $this->field->toPhp(new \stdClass(), $this->model, ''));
    }
}
