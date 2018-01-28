<?php
namespace Awful\Models;

class Comment extends ModelWithMetadata
{
    const BUILTIN_FIELDS = [
        'comment_ID' => 'int',
        'comment_post_ID' => 'int',
        'comment_author' => 'string',
        'comment_author_email' => 'string',
        'comment_author_url' => 'string',
        'comment_author_IP' => 'string',
        'comment_date' => 'date',
        'comment_date_gmt' => 'date',
        'comment_content' => 'string',
        'comment_karma' => 'int',
        'comment_approved' => 'string', //?
        'comment_agent' => 'string',
        'comment_type' => 'string',
        'comment_parent' => self::class,
        'user_id' => 'string',
    ];
}
