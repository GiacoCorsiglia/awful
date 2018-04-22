<?php
namespace Awful\Models;

use WP_Network;

//
// TODO: This whole situation.
//

class Network
{
    protected const OBJECT_TYPE = 'site';

    /**
     * wp_site
     * wp_sitemeta.
     */
    protected const WP_OBJECT_FIELDS = [
        'id' => 'int',
        'domain' => 'string',
        'path' => 'string',
    ];

    /** @var int */
    private $id;

    /** @var WP_Network|null */
    private $wpNetwork;

    public function __construct(int $id = 0)
    {
        $this->id = $id;
    }

    final public function id(): int
    {
        return $this->id;
    }

    /**
     * Fetches the WordPress object representing this network, if one exists.
     *
     * @return WP_Network|null The `WP_Network` object corresponding with
     *                         $this->id, or `null` if none exists.
     */
    final public function wpNetwork(): ?WP_Network
    {
        if ($this->id && !$this->wpNetwork) {
            $this->wpNetwork = get_network($this->id);
        }
        return $this->wpNetwork;
    }

    final public function wpObject(): ?object
    {
        return $this->wpNetwork();
    }

    final public function exists(): bool
    {
        return $this->id && $this->wpNetwork() !== null;
    }

    /**
     * Runs `get_network_option()` for `$this` network.
     *
     * This is also equivalent to `get_site_option()`.
     *
     * @param  string $option
     * @param  mixed  $default
     * @return mixed
     */
    final public function getOption(string $option, $default = false)
    {
        assert((bool) $option, 'Expected non-empty option');

        return get_network_option($this->id, $option, $default);
    }

    /**
     * Runs `update_network_option()` (or `delete_network_option()` if `$value`
     * is `null`) for `$this` network.
     *
     * This is also equivalent to `update_site_option()`.
     *
     * @param  string $option
     * @param  mixed  $value
     * @param  bool   $autoload
     * @return void
     */
    final public function updateOption(string $option, $value, bool $autoload = true): void
    {
        assert((bool) $option, 'Expected non-empty option');

        if ($value === null) {
            delete_network_option($this->id, $option);
        } else {
            update_network_option($this->id, $option, $value);
        }
    }
}
