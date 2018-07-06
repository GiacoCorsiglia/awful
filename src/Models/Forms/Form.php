<?php
namespace Awful\Models\Forms;

use Awful\Models\Database\BlockSet;
use Awful\Models\Database\BlockSetManager;
use Awful\Models\Database\Map\BlockTypeMap;
use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Fields\BlocksField;
use Awful\Models\Model;
use Awful\Models\WordPressModel;
use stdClass;

/**
 * Responsible.
 */
class Form
{
    /**
     * @var stdClass[]
     * @psalm-var array<string, stdClass>
     */
    private $blocks;

    /** @var BlockSetManager */
    private $blockSetManager;

    /** @var BlockTypeMap */
    private $blockTypeMap;

    /**
     * @var (string[])[]
     * @psalm-var array<string, array<int, string>|array<string, array<int, string>>>
     */
    private $errors = ['$errors' => []];

    /**
     * @var string[]
     * @psalm-var array<int, string>
     */
    private $fields;

    /** @var WordPressModel */
    private $owner;

    /**
     * @param BlockTypeMap $blockTypeMap
     * @param WordPressModel $owner The owner to modify.
     * @param stdClass[] $blocks The new blocks.
     * @psalm-param array<string, stdClass> $blocks
     *
     * @param string[]|null $fields The set of fields which are allowed to be
     *                              to be saved for the root model.  Defaults to all of them.
     * @param BlockSetManager $blockSetManager
     * @psalm-param null|array<int, string> $fields
     */
    public function __construct(
        BlockTypeMap $blockTypeMap,
        BlockSetManager $blockSetManager,
        WordPressModel $owner,
        array $blocks,
        array $fields = null
    ) {
        $this->blockTypeMap = $blockTypeMap;
        $this->blockSetManager = $blockSetManager;
        $this->owner = $owner;
        $this->blocks = $blocks;
        // Default to all fields.
        $this->fields = $fields === null ? array_keys($owner->fields()) : $fields;
    }

    /**
     * Validates the data and persist it if valid.
     *
     * Will return `null` on success; will return an associative array of
     * validation errors that can be passed to the client if invalid.  Nothing
     * will be persisted in that case.
     *
     * @return array|null An array of validation errors, if any.
     * @psalm-return array<string, array<int, string>|array<string, array<int, string>>>
     */
    public function saveIfValid(): ?array
    {
        $originalBlockSet = $this->owner->blockSet();
        $originalRootBlock = $originalBlockSet->root();
        $originalBlocks = $originalBlockSet->all();

        // Validate the new root block.  This is a special case since (a) we
        // require a root block; (b) the incoming data may only contain a subset
        // of the root block fields.
        // TODO: Fix bugs having to do with multiple roots existing.
        $newRootBlock = $this->findNewRoot();
        if (!$newRootBlock) {
            return ['$errors' => ['Form data does not include root block.']];
        }
        // Silently reject any unexpected keys.
        $newRootBlock->data = array_intersect_key($newRootBlock->data, array_flip($this->fields), $this->owner->fields());
        // Fill with any missing fields that already were saved.  This is
        // necessary because we allow saving just a subset of the fields of this
        // root block, but doing so must not delete all the other fields!
        // NOTE: This _mutates_ the new block object.
        $newRootBlock->data += $originalRootBlock->data;

        // Create a new block set that---assuming our new data is valid---will
        // contain every block referenced by every field on the owner
        // (recursively).  The input data should only contain blocks needed for
        // the fields this form accepts, but other root block fields may
        // reference other blocks owned by the `owner`.  This is likely a
        // superset of the necessary blocks.  We'll keep track, and delete the
        // unused ones later.
        $intermediateBlocks = $this->blocks + $originalBlocks;
        $intermediateBlockSet = new BlockSet($this->blockTypeMap, $this->owner, $intermediateBlocks);
        // We'll run the clean on this new model instance, then discard it.
        $intermediateOwner = $this->owner->cloneWithBlockSet($intermediateBlockSet);

        // Validate, building up our `errors` dictionary as we go.
        $this->cleanModelRecursively($newRootBlock->uuid, $intermediateOwner);

        // Were any errors actually found?  `$this->errors` will contain an
        // entry for every block that was cleaned, but only non-empty entries
        // indicate an error was found.
        if ($nonEmptyErrors = array_filter($this->errors)) {
            // Allow the consumer to act on the errors; presumably they will be
            // passed to the client.
            return $nonEmptyErrors;
        }

        // The data in this BlockSet has now been cleaned.  However, it may
        // contain blocks that were never referenced in the the recursive clean
        // (kept track of in `$this->errors`).  These should be discarded; they
        // are leftover from the set of `$originalBlocks`, or they were
        // extraneously added by the client.
        // NOTE: Using the `all()` method here is crucial since it's possible
        // that new blocks were created during the clean process.
        $cleanedBlocks = array_intersect_key($intermediateBlockSet->all(), $this->errors);
        // Create a new block set from the exact data that needs to be saved so
        // we can do so.
        $newBlockSet = new BlockSet($this->blockTypeMap, $this->owner, $cleanedBlocks);
        // Persist it.  Existing blocks will be updated; new ones created.
        $this->blockSetManager->save($newBlockSet);

        // There may be blocks in the original set that no longer exist in the
        // new set.  We must delete those.
        $noLongerUsedBlocks = array_diff_key($originalBlocks, $cleanedBlocks);
        $this->blockSetManager->deleteBlocksFor($this->owner, array_keys($noLongerUsedBlocks));

        // Trigger a reload since persisted data has changed.
        $this->owner->reloadBlocks();

        // Indicate success.
        return null;
    }

    private function cleanModelRecursively(string $uuid, Model $model): void
    {
        // Keep track of which blocks we've found.
        if (isset($this->errors[$uuid])) {
            $this->errors['$errors'][] = "Circular reference for block: $uuid";
            return;
        }
        // Clean fields on this model and save whatever errors we may have
        // received.  The `errors` dictionary doubles as an indicator of which
        // blocks we've visited.
        $this->errors[$uuid] = $model->cleanFields() ?: [];

        // Recursively clean children.
        foreach ($model->fields() as $key => $field) {
            if ($field instanceof BlocksField) {
                foreach ($model->get($key) as $childBlock) {
                    // At this point we know `$childBlock instanceof Block`.
                    $this->cleanModelRecursively($childBlock->uuid(), $childBlock);
                }
            }
        }

        if (!$this->errors[$uuid]) {
            // Don't run the model's custom clean until all its fields have been
            // cleaned AND its children have been fully cleaned (recursively).
            // And don't ever run it if the fields on this model already have
            // errors, because that makes all the methods called in `clean()`
            // have to check for invalid field states.
            try {
                $model->clean();
            } catch (ValidationException $e) {
                $this->errors[$uuid]['$errors'] = [$e->getMessage()];
            }
        }
    }

    /**
     * Undocumented function.
     *
     * @return stdClass|null
     */
    private function findNewRoot(): ?stdClass
    {
        $rootType = $this->owner->rootBlockType();
        // This is `BlockSet::firstOfType`.
        foreach ($this->blocks as $uuid => $block) {
            if (($block->type ?? '') === $rootType) {
                return $block;
            }
        }
        return null;
    }
}
