<?php
namespace Awful\Utilities;

interface ObjectCache
{
    /**
     * Wraps `wp_cache_add()`.
     *
     * @param  string $key
     * @param  mixed  $data
     * @param  string $group
     * @param  int    $expire
     * @return bool
     */
    public function add(string $key, $data, string $group = '', int $expire = 0): bool;

    /**
     * Wraps `wp_cache_decr()`.
     *
     * @param  string    $key
     * @param  int       $offset
     * @param  string    $group
     * @return false|int
     */
    public function decr(string $key, int $offset = 1, string $group = '');

    /**
     * Wraps `wp_cache_delete()`.
     *
     * @param  string $key
     * @param  string $group
     * @return bool
     */
    public function delete(string $key, string $group = ''): bool;

    /**
     * Wraps `wp_cache_flush()`.
     *
     * @return bool
     */
    public function flush(): bool;

    /**
     * Wraps `wp_cache_set()`.
     *
     * @param  string      $key
     * @param  string      $group
     * @param  bool        $force
     * @param  bool        $found
     * @return false|mixed
     */
    public function get(string $key, string $group = '', bool $force = false, &$found = null);

    /**
     * Wraps `wp_cache_incr()`.
     *
     * @param  string    $key
     * @param  int       $offset
     * @param  string    $group
     * @return false|int
     */
    public function incr(string $key, int $offset = 1, string $group = '');

    /**
     * Wraps `wp_cache_replace()`.
     *
     * @param  string $key
     * @param  mixed  $data
     * @param  string $group
     * @param  int    $expire
     * @return bool
     */
    public function replace(string $key, $data, string $group = '', int $expire = 0): bool;

    /**
     * Wraps `wp_cache_set()`.
     *
     * @param  string $key
     * @param  mixed  $data
     * @param  string $group
     * @param  int    $expire
     * @return bool
     */
    public function set(string $key, $data, string $group = '', int $expire = 0): bool;

    /**
     * Wraps `wp_cache_switch_to_blog()`.
     *
     * @param  int  $siteId
     * @return void
     */
    public function switchToBlog(int $siteId): void;

    /**
     * Wraps `wp_cache_add_global_groups()`.
     *
     * @param  string[] $groups
     * @return void
     */
    public function addGlobalGroups(array $groups): void;

    /**
     * Wraps `wp_cache_add_non_persistent_groups()`.
     *
     * @param  string[] $groups
     * @return void
     */
    public function addNonPersistentGroups(array $groups): void;
}
