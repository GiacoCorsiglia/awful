<?php
namespace Awful;

/**
 * Tests for the pure functions in `functions.php`.
 */
class FunctionsTest extends AwfulTestCase
{
    public function testUuid()
    {
        $this->assertRegExp('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', uuid(), '`uuid()` returns a valid v4 UUID.');

        // This obviously isn't a real randomness test.
        $this->assertTrue(uuid() !== uuid(), '`uuid()` generates a different value every call');
    }
}
