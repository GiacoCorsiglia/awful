<?php
namespace Awful;

use Awful\Container\Container;
use WP_UnitTestCase;

/**
 * Base class for all tests.
 */
class AwfulTestCase extends WP_UnitTestCase
{
    protected function container()
    {
        return new Container();
    }
}
