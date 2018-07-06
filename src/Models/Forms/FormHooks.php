<?php
namespace Awful\Models\Forms;

use Awful\Context\Context;
use Awful\Models\Database\Map\BlockTypeMap;
use Awful\Models\Database\Map\PostTypeMap;
use Awful\Models\WordPressModel;
use WP_Post;

class FormHooks
{
    /** @var BlockTypeMap */
    private $blockTypeMap;

    /** @var Context */
    private $context;

    /** @var PostTypeMap */
    private $postTypeMap;

    public function __construct(
        BlockTypeMap $blockTypeMap,
        PostTypeMap $postTypeMap,
        Context $context
    ) {
        $this->blockTypeMap = $blockTypeMap;
        $this->postTypeMap = $postTypeMap;
        $this->context = $context;

        add_action('save_post', [$this, 'savePost']);
    }

    public function savePost(
        int $id,
        WP_Post $wpPost,
        bool $update
    ): void {
        $postClass = $this->postTypeMap->classForType($wpPost->post_type);
        $post = new $postClass($this->context->site(), $id);
        /** @psalm-suppress TypeCoercion */
        $this->saveModel($post);
    }

    private function jsonToBlockData(string $postData): array
    {
        $data = json_decode($postData, true);
        if (!is_array($data)) {
            throw new \Exception();
        }
        foreach ($data as $uuid => $blockData) {
            if (!is_array($blockData)) {
                throw new \Exception();
            }
            $data[$uuid] = (object) $blockData;
        }
        return $data;
    }

    private function saveModel(WordPressModel $model): void
    {
        $form = new Form(
            $this->blockTypeMap,
            $model->entityManager()->blockSetManager(),
            $model,
            $this->jsonToBlockData($_POST['awful_data'])
        );

        $errors = $form->saveIfValid();
        if (!$errors) {
            return;
        }
    }
}
