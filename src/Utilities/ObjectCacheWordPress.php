<?php
namespace Awful\Utilities;

class ObjectCacheWordPress
{
    public function add(string $key, $data, string $group = '', int $expire = 0): bool
    {
        return wp_cache_add($key, $data, $group = '', $expire = 0);
    }

    public function decr(string $key, int $offset = 1, string $group = '')
    {
        return wp_cache_decr($key, $offset, $group);
    }

    public function delete(string $key, string $group = ''): bool
    {
        return wp_cache_delete($key, $group);
    }

    public function flush(): bool
    {
        return wp_cache_flush();
    }

    public function get(string $key, string $group = '', bool $force = false, &$found = null)
    {
        return wp_cache_get($key, $group, $force, $found);
    }

    public function incr(string $key, int $offset = 1, string $group = '')
    {
        return wp_cache_incr($key, $offset, $group);
    }

    public function replace(string $key, $data, string $group = '', int $expire = 0): bool
    {
        return wp_cache_replace($key, $data, $group, $expire);
    }

    public function set(string $key, $data, string $group = '', int $expire = 0): bool
    {
        return wp_cache_set($key, $data, $group, $expire);
    }

    public function switchToBlog(int $siteId): void
    {
        wp_cache_switch_to_blog($siteId);
    }

    public function addGlobalGroups(array $groups): void
    {
        wp_cache_add_global_groups($groups);
    }

    public function addNonPersistentGroups(array $groups): void
    {
        wp_cache_add_non_persistent_groups($groups);
    }
}
