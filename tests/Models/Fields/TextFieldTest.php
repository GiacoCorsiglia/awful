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

    public function testToPhp()
    {
        $this->assertSame('', $this->field->toPhp('', $this->model, ''));
        $this->assertSame('foo', $this->field->toPhp('foo', $this->model, ''));
        $this->assertSame('', $this->field->toPhp(false, $this->model, ''));
        $this->assertSame('', $this->field->toPhp(null, $this->model, ''));
        $this->assertSame('1', $this->field->toPhp(true, $this->model, ''));
        $this->assertSame('', $this->field->toPhp(['foo'], $this->model, ''));
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
}
