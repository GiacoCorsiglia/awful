<?php
namespace Awful\Models;

use Awful\Models\Database\Database;
use Awful\Models\Database\EntityManager;
use Awful\Models\Traits\WordPressModelWithMetaTable;
use WP_User;

class User extends WordPressModel
{
    use WordPressModelWithMetaTable;

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

    /** @var EntityManager */
    private $entityManager;

    /** @var int */
    private $id;

    /** @var WP_User|null */
    private $wpUser;

    final public function __construct(
        EntityManager $entityManager,
        int $id = 0
    ) {
        $this->entityManager = $entityManager;
        $this->id = $id;
    }

    final public function entityManager(): EntityManager
    {
        return $this->entityManager;
    }

    final public function siteId(): int
    {
        return is_multisite() ? 1 : 0;
    }

    final public function blockRecordColumn(): string
    {
        return Database::USER_COLUMN;
    }

    final public function blockRecordColumnValue(): int
    {
        return $this->id;
    }

    final public function rootBlockType(): string
    {
        return 'Awful.RootBlocks.User';
    }

    final public function id(): int
    {
        return $this->id;
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

    final public function isLoggedIn(): bool
    {
        return get_current_user_id() === $this->id;
    }

    final protected function metaType(): string
    {
        return 'user';
    }

    final protected function clone(): WordPressModel
    {
        return new static($this->entityManager, $this->id);
    }
}
