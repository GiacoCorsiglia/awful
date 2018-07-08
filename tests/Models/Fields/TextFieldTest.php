<?php
namespace Awful\Models\Fields;

use Awful\AwfulTestCase;
use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Model;

class TextFieldTest extends AwfulTestCase
{
    /** @var TextField */
    private $field;

    /** @var Model */
    private $model;

    public function setUp()
    {
        parent::setUp();

        $this->field = new TextField();
        $this->model = $this->getMockForAbstractClass(Model::class);
    }

    public function testCleanAcceptsStringOrNull()
    {
        $this->assertSame(null, $this->field->clean(null, $this->model));
        $this->assertSame('hello', $this->field->clean('hello', $this->model));
    }

    public function testCleanRejectsBool()
    {
        $this->expectException(ValidationException::class);
        $this->field->clean(false, $this->model);
    }

    public function testCleanWithLengthConstraintsTooLong()
    {
        $field = new TextField([
            'minlength' => 5,
            'maxlength' => 8,
        ]);

        $this->expectException(ValidationException::class);
        $field->clean('123456789', $this->model);
    }

    public function testCleanWithLengthConstraintsTooShort()
    {
        $field = new TextField([
            'minlength' => 5,
            'maxlength' => 8,
        ]);

        $this->expectException(ValidationException::class);
        $field->clean('1234', $this->model);
    }

    public function testCleanWithLengthConstraintsValid()
    {
        $field = new TextField([
            'minlength' => 5,
            'maxlength' => 8,
        ]);

        $this->assertSame('12345', $field->clean('12345', $this->model));
        $this->assertSame('123456', $field->clean('123456', $this->model));
        $this->assertSame('12345678', $field->clean('12345678', $this->model));
    }

    public function testToPhp()
    {
        $this->assertSame('', $this->field->toPhp('', $this->model, ''));
        $this->assertSame('foo', $this->field->toPhp('foo', $this->model, ''));
        $this->assertSame('', $this->field->toPhp(false, $this->model, ''));
        $this->assertSame('', $this->field->toPhp(null, $this->model, ''));
        $this->assertSame('1', $this->field->toPhp(true, $this->model, ''));
        $this->assertSame('', $this->field->toPhp(['foo'], $this->model, ''));
    }
}
