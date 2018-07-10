<?php
namespace Awful\Models;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;
use Awful\Models\Database\EntityManager;

class UserTest extends AwfulTestCase
{
    public function testBlockRecordColumn()
    {
        $this->assertSame(Database::USER_COLUMN, $this->instance()->blockRecordColumn());
    }

    public function testBlockRecordColumnValue()
    {
        $this->assertSame(3, $this->instance(3)->blockRecordColumnValue());
    }

    public function testColumn()
    {
        $this->assertSame(Database::USER_COLUMN, $this->instance()->blockRecordColumn());
    }

    public function testEntityManager()
    {
        $em = $this->createMock(EntityManager::class);
        $user = new User($em, 0);
        $this->assertSame($em, $user->entityManager());
    }

    public function testExists()
    {
        $wpUser = $this->factory->user->create_and_get();
        $this->assertTrue($this->instance($wpUser->ID)->exists());
        $this->assertFalse($this->instance(1234567)->exists());
        $this->assertFalse($this->instance(0)->exists());
    }

    public function testGetAndUpdateMeta()
    {
        $wpUser1 = $this->factory->user->create_and_get();
        $wpUser2 = $this->factory->user->create_and_get();
        $user1 = $this->instance($wpUser1->ID);
        $user2 = $this->instance($wpUser2->ID);

        update_user_meta($wpUser1->ID, 'test-meta', ['test' => 'value']);
        $this->assertSame(['test' => 'value'], $user1->getMeta('test-meta'));
        $this->assertNull($user2->getMeta('test-meta'));

        $user1->updateMeta('test-meta', ['test' => 'updated']);
        $this->assertSame(['test' => 'updated'], get_user_meta($wpUser1->ID, 'test-meta', true));
        $this->assertSame(['test' => 'updated'], $user1->getMeta('test-meta'));
        $this->assertNull($user2->getMeta('test-meta'));

        $user2->updateMeta('other-meta', ['test' => 'foo']);
        $this->assertSame(['test' => 'foo'], get_user_meta($wpUser2->ID, 'other-meta', true));
        $this->assertSame(['test' => 'foo'], $user2->getMeta('other-meta'));
        $this->assertSame(['test' => 'updated'], $user1->getMeta('test-meta'));

        $user1->updateMeta('test-meta', null);
        $this->assertNull($user1->getMeta('test-meta'));
        $this->assertSame([], get_user_meta($wpUser1->ID, 'test-meta'), 'The meta row was actually deleted when set to `null`');
    }

    public function testId()
    {
        $wpUser = $this->factory->user->create_and_get();
        $this->assertSame($wpUser->ID, $this->instance($wpUser->ID)->id());
        $this->assertSame(1234567, $this->instance(1234567)->id());
        $this->assertSame(0, $this->instance(0)->id());
    }

    public function testIsLoggedIn()
    {
        $wpUser = $this->factory->user->create_and_get();
        $user = $this->instance($wpUser->ID);
        $this->assertFalse($user->isLoggedIn());

        wp_set_current_user($user->id());
        $this->assertTrue($user->isLoggedIn());
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.User', $this->instance()->rootBlockType());
    }

    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, $this->instance()->siteId());
    }

    public function testWpUserAndWpObject()
    {
        $wpUser = $this->factory->user->create_and_get();
        $user = $this->instance($wpUser->ID);
        $this->assertSame($wpUser->ID, $user->wpUser()->ID);
        $this->assertSame($user->wpUser(), $user->wpObject());

        $newUser = $this->instance(1234567);
        $this->assertNull($newUser->wpUser());
        $this->assertSame($newUser->wpUser(), $newUser->wpObject());
    }

    private function instance(int $userId = 1): User
    {
        $em = $this->createMock(EntityManager::class);
        return new User($em, $userId);
    }
}
