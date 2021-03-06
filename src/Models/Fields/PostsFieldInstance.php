<?php
namespace Awful\Models\Fields;

use Awful\Models\GenericPost;
use Awful\Models\Model;
use Awful\Models\Site;

class PostsFieldInstance extends ObjectsFieldInstance
{
    /** @var string */
    private $fieldKey;

    /** @var Model */
    private $model;

    public function __construct(
        array $ids,
        Model $model,
        string $fieldKey,
        ?Site $site
    ) {
        $this->model = $model;
        $this->fieldKey = $fieldKey;

        if (!$site) {
            // Really shouldn't get here.
            return;
        }

        $posts = $site->allPosts()->fetchByIds(...$ids);

        $this->ids = array_keys($posts);
        $this->objects = array_values($posts);
    }

    protected function emit(): void
    {
        $this->model->set([$this->fieldKey => $this->ids]);
    }

    protected function validateAndGetId(object $object): int
    {
        if (!$object instanceof GenericPost) {
            throw new \Exception();
        }
        return $object->id();
    }
}
