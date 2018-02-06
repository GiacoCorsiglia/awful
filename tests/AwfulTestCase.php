<?php
namespace Awful;

use Awful\Container\Container;
use WP_UnitTestCase;

/**
 * Base class for all tests.
 *
 * @property-read \WP_UnitTest_Factory $factory Getter for a new factory
 *                                              provided by WP_UnitTestCase.
 */
class AwfulTestCase extends WP_UnitTestCase
{
    protected function container()
    {
        return new Container();
    }
}
