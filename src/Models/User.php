<?php
namespace Awful\Models;

use Awful\Models\Traits\BlockOwnerTrait;
use Awful\Models\Traits\ModelWithMetaTable;
use WP_User;

class User extends Model implements BlockOwnerModel, WordPressModel
{
    use ModelWithMetaTable;
    use BlockOwnerTrait;

    protected const WP_OBJECT_FIELDS = [
        'ID' => 'int',
        'user_login' => 'string',
        'user_pass' => 'string/password',
        'user_nicename' => 'string',
        'user_email' => 'string',
        'user_url' => 'string',
        'user_registered' => 'datetime',
        'user_activation_key' => 'string',
        'user_status' => 'int', // ?
        'display_name' => 'string',
        // multisite only
        'spam' => 'bool',
        'deleted' => 'bool',
    ];

    /** @var int */
    private $id;

    /** @var WP_User|null */
    private $wpUser;

    final public function __construct(
        int $id = 0
    ) {
        $this->id = $id;
    }

    /**
     * Fetches the WordPress object representing this user, if one exists.
     *
     * @return WP_User|null The `WP_User` object corresponding with $this->id,
     *                      or `null` if none exists.
     */
    final public function wpUser(): ?WP_User
    {
        if ($this->id && !$this->wpUser) {
            $this->wpUser = get_user_by('id', $this->id) ?: null;
        }
        return $this->wpUser;
    }

    final public function wpObject(): ?object
    {
        return $this->wpUser();
    }

    final public function exists(): bool
    {
        return $this->id && $this->wpUser() !== null;
    }

    final public function id(): int
    {
        return $this->id;
    }

    final protected function metaType(): string
    {
        return 'user';
    }

    final protected function rootBlockType(): string
    {
        return 'Awful.Blocks.Root.User';
    }
}
