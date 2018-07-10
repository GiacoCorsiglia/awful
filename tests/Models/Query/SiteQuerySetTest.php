<?php
namespace Awful\Models\Query;

use Awful\AwfulTestCase;
use Awful\Models\Database\EntityManager;

class SiteQuerySetTest extends AwfulTestCase
{
    /** @var SiteQuerySet */
    private $qs;

    public function setUp()
    {
        parent::setUp();

        $this->skipWithoutMultisite();

        $em = $this->createMock(EntityManager::class);
        $this->qs = new SiteQuerySet($em);
    }

    public function testFetchById()
    {
        $wpSite = $this->factory->blog->create_and_get();
        $site = $this->qs->fetchById($wpSite->blog_id);
        $this->assertNotNull($site);
        $this->assertSame((int) $wpSite->blog_id, $site->id());

        $this->assertNull($this->qs->fetchById(1234567));
    }
}
