<?php
namespace Awful\Models;

class User extends ModelWithMetadata
{
    const BUILTIN_FIELDS = [
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
}
