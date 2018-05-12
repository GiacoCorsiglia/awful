<?php
namespace Awful;

use Awful\Container\Container;
use Awful\Models\Database\EntityManager;
use Awful\Models\Site;
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

    protected function mockSite(int $id = 1): Site
    {
        $em = $this->createMock(EntityManager::class);
        return new Site($em, is_multisite() ? $id : 0);
    }
}
