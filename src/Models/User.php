<?php
namespace Awful\Models;

use Awful\Models\Fields\FieldsResolver;
use Awful\Models\Traits\ModelWithMetaTable;

class User extends Model
{
    use ModelWithMetaTable;

    protected const WORDPRESS_OBJECT_FIELDS = [
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

    final protected function __construct(
        int $id = 0,
        FieldsResolver $resolver = null
    ) {
        $this->id = 0;

        $this->initializeFieldsResolver($resolver);
    }

    final protected function getMetaType(): string
    {
        return 'user';
    }

    final public function exists(): bool
    {
        // TODO
        return true;
    }
}
