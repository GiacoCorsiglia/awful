<?php
namespace Awful\Models;

use Awful\Models\Traits\ModelOwnedBySite;
use Awful\Models\Traits\ModelWithMetaTable;

class Comment extends Model
{
    use ModelWithMetaTable;
    use ModelOwnedBySite;

    protected const WORDPRESS_OBJECT_FIELDS = [
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

    final protected function fetchData(): void
    {
        // TODO
    }

    final protected function getMetaType(): string
    {
        return 'comment';
    }

    final public function exists(): bool
    {
        return (bool) get_comment($this->id);
    }
}
