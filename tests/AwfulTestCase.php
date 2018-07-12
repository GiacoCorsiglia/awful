<?php
namespace Awful;

use Awful\Container\Container;
use Awful\Models\Database\EntityManager;
use Awful\Models\Exceptions\ValidationException;
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
    /**
     * @param callable $predicate
     * @psalm-param callable(mixed, int|string): bool $predicate
     * @param array|\Iterable $array
     * @param string $message
     */
    public static function assertArrayContainsPredicate(callable $predicate, $array, $message = '')
    {
        $found = false;
        foreach ($array as $key => $value) {
            if ($predicate($value, $key)) {
                $found = true;
                break;
            }
        }
        static::assertTrue($found, $message);
    }

    /**
     * @param string $method
     * @param mixed $return
     * @return Closure
     */
    public static function methodPredicate(string $method, $return): Closure
    {
        return function (object $object) use ($method, $return): bool {
            return $object->{$method}() === $return;
        };
    }

    public function expectValidationException(): void
    {
        $this->expectException(ValidationException::class);
    }

    protected function container()
    {
        return new Container();
    }

    protected function mockSite(int $id = 1): Site
    {
        if (is_multisite() && !get_site($id)) {
            $this->factory->blog->create([
                'blog_id' => $id,
            ]);
        }
        $em = $this->createMock(EntityManager::class);
        return new Site($em, is_multisite() ? $id : 0);
    }
}
