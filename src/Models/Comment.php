<?php
namespace Awful\Models;

use Awful\Models\Database\Database;
use Awful\Models\Traits\WordPressModelOwnedBySite;
use Awful\Models\Traits\WordPressModelWithMetaTable;
use WP_Comment;

class Comment extends WordPressModel
{
    use WordPressModelOwnedBySite;
    use WordPressModelWithMetaTable;

    protected const WP_OBJECT_FIELDS = [
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

    /** @var WP_Comment|null */
    private $wpComment;

    final public function blockRecordColumn(): string
    {
        return Database::COMMENT_COLUMN;
    }

    final public function rootBlockType(): string
    {
        return 'Awful.RootBlocks.Comment';
    }

    /**
     * Fetches the WordPress object representing this comment, if one exists.
     *
     * @return WP_Comment|null The `WP_Comment` object corresponding with $this->id,
     *                         or `null` if none exists.
     */
    final public function wpComment(): ?WP_Comment
    {
        if ($this->id && !$this->wpComment) {
            $this->wpComment = $this->callInSiteContext('get_comment', $this->id);
        }
        return $this->wpComment;
    }

    final public function wpObject(): ?object
    {
        return $this->wpComment();
    }

    final public function exists(): bool
    {
        return $this->id && $this->wpComment() !== null;
    }

    final protected function metaType(): string
    {
        return 'comment';
    }
}
