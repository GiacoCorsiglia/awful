<?php
namespace Awful\Models\Forms;

use Awful\AwfulTestCase;
use Awful\Models\Database\BlockSetManager;
use Awful\Models\Database\DatabaseMock;
use Awful\Models\Database\EntityManager;
use Awful\Models\Database\Map\BlockTypeMap;
use Awful\Models\Site;

class FormTest extends AwfulTestCase
{
    /** @var BlockSetManager */
    private $blockSetManager;

    /** @var BlockTypeMap */
    private $blockTypeMap;

    /** @var DatabaseMock */
    private $db;

    /** @var Site */
    private $site;

    public function setUp()
    {
        parent::setUp();

        $this->db = new DatabaseMock();
        $this->blockTypeMap = new BlockTypeMap([
            FormTest\TestBlock1::class => 'test_block_1',
            FormTest\TestBlock2::class => 'test_block_2',
        ]);
        $this->blockSetManager = new BlockSetManager($this->db, $this->blockTypeMap);
        $entityManager = new EntityManager($this->blockSetManager);

        if (is_multisite()) {
            $siteId = (int) $this->factory->blog->create_and_get()->blog_id;
        } else {
            $siteId = 0;
        }
        $this->site = new FormTest\TestSite($entityManager, $siteId);
    }

    public function testSaveFromEmptyWhenInvalid()
    {
        $this->assertTrue($this->site->exists());

        $this->assertSame('', $this->site->get('site_text'));
        $this->assertCount(0, $this->site->get('site_children'));

        $form = new Form($this->blockTypeMap, $this->blockSetManager, $this->site, [
            'uuid1' => (object) [
                'uuid' => 'uuid1',
                'type' => $this->site->rootBlockType(),
                'data' => [
                    'site_text' => 'invalid',
                    'site_children' => ['uuid2', 'fake1'],
                ],
            ],
            'uuid2' => (object) [
                'uuid' => 'uuid2',
                'type' => 'test_block_1',
                'data' => [
                    'block_1_text' => 'foobar',
                    'block_1_children' => ['uuid3'],
                ],
            ],
            'uuid3' => (object) [
                'uuid' => 'uuid3',
                'type' => 'test_block_2',
                'data' => [
                    'block_2_text' => 'invalid',
                ],
            ],
        ]);

        $beforeBlockSet = $this->site->blockSet();

        $errors = $form->saveIfValid();

        $this->assertSame($beforeBlockSet, $this->site->blockSet(), 'Invalid save should not trigger reload.');

        $this->assertSame([
            'uuid1' => [
                'site_children' => ["Block 'fake1' does not exist."],
                // No '$errors' key because Model clean should not run if any of
                // its fields are invalid.
            ],
            // NO uuid2
            'uuid3' => [
                '$errors' => ['clean: invalid'],
            ],
        ], $errors);
    }

    public function testSaveFromEmptyWhenValid()
    {
        $this->assertTrue($this->site->exists());

        $this->assertSame('', $this->site->get('site_text'));
        $this->assertCount(0, $this->site->get('site_children'));

        $form = new Form($this->blockTypeMap, $this->blockSetManager, $this->site, [
            'uuid1' => (object) [
                'uuid' => 'uuid1',
                'type' => $this->site->rootBlockType(),
                'data' => [
                    'site_text' => 'example 1',
                    'site_children' => ['uuid2', 'uuid3'],
                    // This field should be disregarded.
                    'site_other_text' => 'blah',
                ],
            ],
            'uuid2' => (object) [
                'uuid' => 'uuid2',
                'type' => 'test_block_1',
                'data' => [
                    'block_1_text' => 'example 2',
                    'block_1_children' => ['uuid4', 'uuid5'],
                ],
            ],
            'uuid3' => (object) [
                'uuid' => 'uuid3',
                'type' => 'test_block_1',
                'data' => [
                    'block_1_text' => 'example 3',
                    'block_1_children' => [],
                ],
            ],
            'uuid4' => (object) [
                'uuid' => 'uuid4',
                'type' => 'test_block_2',
                'data' => [
                    'block_2_text' => 'example 4',
                ],
            ],
            'uuid5' => (object) [
                'uuid' => 'uuid5',
                'type' => 'test_block_2',
                'data' => [
                    'block_2_text' => 'example 5',
                ],
            ],
            // This one is unused and should not be saved.
            'uuid6' => (object) [
                'uuid' => 'uuid6',
            ],
        ], [
            'site_text',
            'site_children',
        ]);

        $beforeBlockSet = $this->site->blockSet();

        $errors = $form->saveIfValid();

        $this->assertNotSame($beforeBlockSet, $this->site->blockSet(), 'Successful save triggers a reload.');

        $this->assertNull($errors, 'Form is valid so should not produce errors');

        $this->assertSame('example 1', $this->site->get('site_text'));
        $this->assertSame('', $this->site->get('site_other_text'));

        $children = $this->site->get('site_children');
        $this->assertCount(2, $this->site->get('site_children'));
        $this->assertSame('uuid2', $children[0]->uuid());
        $this->assertSame('example 2', $children[0]->get('block_1_text'));
        $this->assertSame('uuid3', $children[1]->uuid());
        $this->assertSame('example 3', $children[1]->get('block_1_text'));

        $grandchildren = $children[0]->get('block_1_children');
        $this->assertCount(2, $grandchildren);
        $this->assertSame('uuid4', $grandchildren[0]->uuid());
        $this->assertSame('example 4', $grandchildren[0]->get('block_2_text'));
        $this->assertSame('uuid5', $grandchildren[1]->uuid());
        $this->assertSame('example 5', $grandchildren[1]->get('block_2_text'));

        $this->assertArrayNotHasKey('uuid6', $this->site->blockSet()->all(), 'Extra blocks should not be saved.');
    }

    public function testSaveWithExisting()
    {
        $this->assertTrue($this->site->exists());

        $siteId = $this->site->id();
        $this->assertEmpty($this->db->getBlocksForSite($siteId));

        $this->db->setBlocksForSite($siteId, [
            [
                'uuid' => 'uuid1',
                'for_site' => 1,
                'type' => $this->site->rootBlockType(),
                'data' => [
                    'site_text' => 'site text original',
                    'site_other_text' => 'site other text',
                    'site_children' => ['uuid2', 'uuid3'],
                ],
            ],
            [
                'uuid' => 'uuid2',
                'for_site' => 1,
                'type' => 'test_block_1',
                'data' => [
                    'block_1_text' => 'block 1 text 1',
                    'block_1_children' => ['uuid4', 'uuid5'],
                ],
            ],
            [
                'uuid' => 'uuid3',
                'for_site' => 1,
                'type' => 'test_block_1',
                'data' => [
                    'block_1_text' => 'block 1 text 1',
                    'block_1_children' => ['uuid6', 'uuid7'],
                ],
            ],
            [
                'uuid' => 'uuid4',
                'for_site' => 1,
                'type' => 'test_block_2',
                'data' => [
                    'block_2_text' => 'block 2 text 1',
                ],
            ],
            [
                'uuid' => 'uuid5',
                'for_site' => 1,
                'type' => 'test_block_2',
                'data' => [
                    'block_1_text' => 'block 2 text 2',
                ],
            ],
            [
                'uuid' => 'uuid6',
                'for_site' => 1,
                'type' => 'test_block_2',
                'data' => [
                    'block_2_text' => 'block 2 text 1',
                ],
            ],
            [
                'uuid' => 'uuid7',
                'for_site' => 1,
                'type' => 'test_block_2',
                'data' => [
                    'block_1_text' => 'block 2 text 2',
                ],
            ],
        ]);

        $this->assertSame('site text original', $this->site->get('site_text'));
        $this->assertSame('site other text', $this->site->get('site_other_text'));
        $siteChildren = $this->site->get('site_children');
        $this->assertCount(2, $siteChildren);
        $this->assertCount(2, $siteChildren[0]->get('block_1_children'));
        $this->assertCount(2, $siteChildren[1]->get('block_1_children'));

        $form = new Form($this->blockTypeMap, $this->blockSetManager, $this->site, [
            'uuid1' => (object) [
                'id' => $this->site->blockSet()->root()->id,
                'uuid' => 'uuid1',
                'type' => $this->site->rootBlockType(),
                'data' => [
                    'site_text' => 'site text updated',
                    'site_children' => ['uuid2'],
                    // This field should be disregarded.
                    'site_other_text' => 'blah',
                ],
            ],
            'uuid2' => (object) [
                'id' => $siteChildren[0]->id(),
                'uuid' => 'uuid2',
                'type' => 'test_block_1',
                'data' => [
                    'block_1_text' => 'example 2',
                    'block_1_children' => ['uuid4', 'uuid8'],
                ],
            ],
            'uuid4' => (object) [
                'id' => $siteChildren[0]->get('block_1_children')[0]->id(),
                'uuid' => 'uuid4',
                'type' => 'test_block_2',
                'data' => [
                    'block_2_text' => 'example 4',
                ],
            ],
            'uuid8' => (object) [
                'uuid' => 'uuid8',
                'type' => 'test_block_2',
                'data' => [
                    'block_2_text' => 'example 5',
                ],
            ],
        ], [
            'site_text',
            'site_children',
        ]);

        $errors = $form->saveIfValid();

        $this->assertNull($errors);

        $this->assertSame('site text updated', $this->site->get('site_text'));
        $this->assertSame('site other text', $this->site->get('site_other_text'));

        $siteChildren = $this->site->get('site_children');
        $this->assertCount(1, $siteChildren);
        $block1Children = $siteChildren[0]->get('block_1_children');
        $this->assertCount(2, $block1Children);
        $this->assertSame('uuid4', $block1Children[0]->uuid());
        $this->assertSame('uuid8', $block1Children[1]->uuid());

        $this->assertCount(4, $this->db->getBlocksForSite($siteId), 'Excess blocks are deleted from database; existing blocks are updated; new one added');
    }
}
namespace Awful\Models\Forms\FormTest;

use Awful\Models\Block;
use Awful\Models\Exceptions\ValidationException;
use Awful\Models\Fields\BlocksField;
use Awful\Models\Fields\TextField;
use Awful\Models\Site;

class TestSite extends Site
{
    protected static function registerFields(): array
    {
        return [
            'site_text' => new TextField(),
            'site_other_text' => new TextField(),
            'site_children' => new BlocksField([
                'types' => [TestBlock1::class],
            ]),
        ];
    }

    public function clean(): void
    {
        if ($this->get('site_text') === 'invalid') {
            throw new ValidationException('clean: invalid');
        }
    }
}

class TestBlock1 extends Block
{
    protected static function registerFields(): array
    {
        return [
            'block_1_text' => new TextField(),
            'block_1_children' => new BlocksField([
                'types' => [TestBlock2::class],
            ]),
        ];
    }

    public function clean(): void
    {
        if ($this->get('block_1_text') === 'invalid') {
            throw new ValidationException('clean: invalid');
        }
    }
}

class TestBlock2 extends Block
{
    protected static function registerFields(): array
    {
        return [
            'block_2_text' => new TextField(),
        ];
    }

    public function clean(): void
    {
        if ($this->get('block_2_text') === 'invalid') {
            throw new ValidationException('clean: invalid');
        }
    }
}
