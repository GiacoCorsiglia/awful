<?php
namespace Awful\Models;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;
use Awful\Models\Database\EntityManager;

class SiteTest extends AwfulTestCase
{
    public function testBlockRecordColumn()
    {
        $this->assertSame(Database::SITE_COLUMN, $this->instance()->blockRecordColumn());
    }

    public function testBlockRecordColumnValue()
    {
        // Should always be `1` since it's a boolean field.
        $this->assertSame(1, $this->instance()->blockRecordColumnValue());
    }

    public function testEntityManager()
    {
        $em = $this->createMock(EntityManager::class);
        $site = new Site($em, is_multisite() ? 1 : 0);
        $this->assertSame($em, $site->entityManager());
    }

    public function testExists()
    {
        if (is_multisite()) {
            $wpSite = $this->factory->blog->create_and_get();
            $this->assertTrue($this->instance($wpSite->blog_id)->exists());
            $this->assertFalse($this->instance(1234567)->exists());
            $this->assertFalse($this->instance(0)->exists());
        } else {
            $this->assertTrue($this->instance()->exists());
        }
    }

    public function testGetAndUpdateOption()
    {
        if (is_multisite()) {
            $wpSite1 = $this->factory->blog->create_and_get();
            $wpSite2 = $this->factory->blog->create_and_get();

            $site1 = $this->instance($wpSite1->blog_id);
            $site2 = $this->instance($wpSite2->blog_id);

            add_blog_option($wpSite1->blog_id, 'test_option', 'site1');
            add_blog_option($wpSite2->blog_id, 'test_option', 'site2');

            $this->assertSame('site1', $site1->getOption('test_option'));
            $this->assertSame('site2', $site2->getOption('test_option'));

            $site1->updateOption('test_option', 'site1-updated');
            $this->assertSame('site1-updated', $site1->getOption('test_option'));
            $this->assertSame('site1-updated', get_blog_option($wpSite1->blog_id, 'test_option'));
            // Remains unchanged:
            $this->assertSame('site2', $site2->getOption('test_option'));
            $this->assertSame('site2', get_blog_option($wpSite2->blog_id, 'test_option'));

            $site1->updateOption('test_option', null);
            // `false` indicates it no longer exists in database.
            $this->assertSame(false, $site1->getOption('test_option'));
            $this->assertSame(false, get_blog_option($wpSite1->blog_id, 'test_option'));
            // Remains unchanged:
            $this->assertSame('site2', $site2->getOption('test_option'));
            $this->assertSame('site2', get_blog_option($wpSite2->blog_id, 'test_option'));
        } else {
            $site = $this->instance();

            add_option('test_option', 'foo');

            $this->assertSame('foo', $site->getOption('test_option'));

            $site->updateOption('test_option', 'bar');
            $this->assertSame('bar', $site->getOption('test_option'));
            $this->assertSame('bar', get_option('test_option'));

            $site->updateOption('test_option', null);
            // `false` indicates it no longer exists in database.
            $this->assertSame(false, $site->getOption('test_option'));
            $this->assertSame(false, get_option('test_option'));
        }
    }

    public function testIdAndSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, $this->instance($siteId)->id());
        $this->assertSame($siteId, $this->instance($siteId)->siteId());
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.Site', $this->instance()->rootBlockType());
    }

    public function testWpSiteAndWpObject()
    {
        $this->skipWithoutMultisite();

        $wpSite = $this->factory->blog->create_and_get();
        $site = $this->instance($wpSite->blog_id);

        // `get_site()` returns new instances.
        $this->assertSame($wpSite->blog_id, $site->wpSite()->blog_id);
        $this->assertSame($site->wpObject(), $site->wpSite());

        $newSite = $this->instance(1234567); // No way this site exists in DB.
        $this->assertNull($newSite->wpSite());
        $this->assertSame($newSite->wpObject(), $newSite->wpSite());
    }

    private function instance(int $siteId = null): Site
    {
        if ($siteId === null) {
            $siteId = is_multisite() ? 1 : 0;
        } elseif (!is_multisite()) {
            $siteId = 0;
        }
        $em = $this->createMock(EntityManager::class);
        return new Site($em, $siteId);
    }
}
