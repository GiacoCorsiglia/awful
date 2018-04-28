<?php
namespace Awful\Models\Fields;

use Awful\Models\Block;
use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Model;
use Awful\Models\Query\SiteQuerySet;
use Awful\Models\Site;
use Awful\Models\User;
use Awful\Models\WordPressModel;

/**
 * Allows saving a reference to another post.
 */
class PostsField extends Field
{
    public function toPhp($value, Model $model, string $fieldKey)
    {
        if (!is_array($value)) {
            $value = [];
        } else {
            $value = array_map('absint', $value);
        }

        return new PostsFieldInstance($value, $model, $fieldKey, $this->determineSite($model));
    }

    public function clean($value, Model $model): array
    {
        if (!$value) {
            return [];
        }

        if (!is_array($value)) {
            throw new ValidationException('Expected an array of post ids.');
        }

        // TODO: Maybe don't validate so strictly since it will make saving
        // posts too slow.
        $site = $this->determineSite($model);

        if (!$site) {
            throw new ValidationException('Site does not exist.');
        }

        $existingPosts = $site->allPosts()->fetchByIds(...$value);
        $badIds = [];

        foreach ($value as $id) {
            if (!isset($existingPosts[$id])) {
                $badIds[] = $id;
            }
        }

        if ($badIds) {
            throw new ValidationException('Some post ids do not exist: ' . implode(',', $badIds));
        }

        return $value;
    }

    private function determineSite(Model $model): ?Site
    {
        /** @var WordPressModel */
        $wpModel = $model instanceof Block ? $model->owner() : $model;
        $blockSetManager = $wpModel->blockSet()->manager();

        if (!empty($this->args['site_id'])) {
            return (new SiteQuerySet($blockSetManager))->fetchById($$this->args['site_id']);
        }
        if ($wpModel instanceof User) {
            // Users are on the main site by default in multisite.
            return (new SiteQuerySet($blockSetManager))->fetchById(is_multisite() ? 1 : 0);
        }
        if ($wpModel instanceof Site) {
            return $wpModel;
        }
        /** @psalm-suppress UndefinedMethod */
        return $wpModel->site();
    }
}
