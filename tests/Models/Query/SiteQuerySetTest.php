<?php
namespace Awful\Models\Query;

use Awful\AwfulTestCase;
use Awful\Exceptions\ImmutabilityException;
use Awful\Models\Database\EntityManager;
use Awful\Models\Site;

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

    public function testAny()
    {
        $this->assertTrue($this->qs->any());
        $this->assertFalse($this->qs->chunk(1, 1000000)->any());
    }

    public function testFetchAndArrayGetMethods()
    {
        $numSites = 11;
        // The main Site will already exist.
        $this->factory->blog->create_many($numSites - 1);

        $previousQueryCount = get_num_queries();
        $this->flush_cache();

        $sites = $this->qs->fetch();
        $this->assertCount($numSites, $sites);
        $this->assertContainsOnlyInstancesOf(Site::class, $sites);

        // Let's make sure each method does not trigger more queries.
        $queryCount = get_num_queries();
        $this->assertSame($previousQueryCount + 2, $queryCount, 'A query was actually run.');

        $this->assertCount($numSites, $this->qs);

        $this->assertContainsOnlyInstancesOf(Site::class, $this->qs);
        $this->assertArrayHasKey(get_current_blog_id(), $sites);
        $this->assertArrayHasKey(get_current_blog_id(), $this->qs);
        // Test offsetExists.
        $this->assertTrue(isset($this->qs[get_current_blog_id()]));
        foreach ($this->qs as $key => $site) {
            $this->assertSame($site->id(), $key, 'Array should be keyed by Site ID');
        }

        $this->assertSame($sites, $this->qs->fetch());

        $this->assertSame($queryCount, get_num_queries(), 'No additional queries were run');
    }

    public function testFetchById()
    {
        $wpSite = $this->factory->blog->create_and_get();
        $site = $this->qs->fetchById($wpSite->blog_id);
        $this->assertNotNull($site);
        $this->assertSame((int) $wpSite->blog_id, $site->id());

        $this->assertNull($this->qs->fetchById(1234567));
    }

    public function testFilterMethods()
    {
        $this->assertSame(0, $this->qs->wpSiteQuery()->query_vars['number'], 'Defaults to loading all sites');

        $this->assertTrue($this->qs->archived(true)->wpSiteQuery()->query_vars['archived']);
        $this->assertTrue($this->qs->deleted(true)->wpSiteQuery()->query_vars['deleted']);
        $this->assertTrue($this->qs->mature(true)->wpSiteQuery()->query_vars['mature']);
        $this->assertTrue($this->qs->public(true)->wpSiteQuery()->query_vars['public']);
        $this->assertTrue($this->qs->spam(true)->wpSiteQuery()->query_vars['spam']);

        $chunkedQueryVars = $this->qs->chunk(2, 5)->wpSiteQuery()->query_vars;
        $this->assertSame(2, $chunkedQueryVars['number']);
        $this->assertSame(5, $chunkedQueryVars['offset']);
    }

    public function testFirst()
    {
        $this->assertInstanceOf(Site::class, $this->qs->first());
        $this->assertNull($this->qs->chunk(1, 1000000)->first());
    }

    public function testIds()
    {
        $numSites = 11;
        // The main Site will already exist.
        $this->factory->blog->create_many($numSites - 1);

        $ids = $this->qs->ids();
        $this->assertCount($numSites, $ids);
        $this->assertContainsOnly('int', $ids, true);
    }

    public function testOffsetSetThrows()
    {
        $this->expectException(ImmutabilityException::class);
        $this->qs[5] = 'foo';
    }

    public function testOffsetUnsetThrows()
    {
        $this->expectException(ImmutabilityException::class);
        unset($this->qs[5]);
    }
}
