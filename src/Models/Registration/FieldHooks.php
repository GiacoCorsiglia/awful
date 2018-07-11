<?php
namespace Awful\Models\Registration;

use Awful\Context\Context;
use Awful\Models\Database\Map\BlockTypeMap;
use Awful\Models\Database\Map\Exceptions\UnknownTypeException;
use Awful\Models\Database\Map\PostTypeMap;
use Awful\Models\WordPressModel;
use WP_Post;

class FieldHooks
{
    /** @var BlockTypeMap */
    private $blockTypeMap;

    /** @var Context */
    private $context;

    /** @var PostTypeMap */
    private $postTypeMap;

    public function __construct(
        PostTypeMap $postTypeMap,
        BlockTypeMap $blockTypeMap,
        Context $context
    ) {
        $this->postTypeMap = $postTypeMap;
        $this->blockTypeMap = $blockTypeMap;
        $this->context = $context;

        if (!is_admin()) {
            return;
        }

        add_action('add_meta_boxes', [$this, 'addMetaBoxes'], 10, 2);
    }

    public function addMetaBoxes(string $postType, WP_Post $wpPost = null): void
    {
        try {
            $postClass = $this->postTypeMap->classForType($postType);
        } catch (UnknownTypeException $e) {
            return;
        }

        /** @var WordPressModel */
        $post = new $postClass($this->context->site(), $wpPost->ID);

        $this->addMetaBox('post', $post);
    }

    /**
     * @param mixed $_
     * @param mixed $box
     * @return void
     */
    public function renderMetaBox($_, $box): void
    {
        echo '<h2>Awful</h2>';

        [$owner] = $box['args'];

        echo '<script type="text/javascript">', "\n";
        echo 'var AWFUL_BLOCK_TYPES = ', $this->encodeBlockTypes($owner), ";\n";
        echo 'var AWFUL_BLOCK_SET = ', $this->encodeBlockSet($owner) , ";\n";
        echo '</script>', "\n";

        echo '<div id="awful-app"></div>', "\n";
    }

    private function addMetaBox(string $screen, WordPressModel $owner): void
    {
        if (!$owner->fields()) {
            return;
        }
        add_meta_box(
            'awful-meta-box',
            'Awful',
            [$this, 'renderMetaBox'],
            $screen,
            'normal',
            'default',
            [$owner]
        );
    }

    private function encodeBlockSet(WordPressModel $owner = null): string
    {
        if (!$owner) {
            return '{}';
        }
        $blocks = $owner->blockSet()->all();
        if (!$blocks) {
            return '{}'; // PHP converts an empty array to `[]` instead.
        }
        return json_encode($blocks);
    }

    private function encodeBlockTypes(WordPressModel $owner): string
    {
        $blockTypes = [];
        foreach ($this->blockTypeMap->typeToClassMap() as $type => $blockClass) {
            $blockTypes[$type] = [
                'label' => $blockClass::label(),
                'fields' => $blockClass::fields(),
            ];
        }

        return json_encode([
            $owner->rootBlockType() => [
                'label' => 'ROOT',
                'fields' => $owner->fields(),
            ],
        ] + $blockTypes);
    }
}
