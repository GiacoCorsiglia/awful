<?php
namespace Awful\Models;

use Awful\AwfulTestCase;
use Awful\Models\Database\Database;
use Awful\Models\Database\EntityManager;

class UserTest extends AwfulTestCase
{
    public function testSiteId()
    {
        $siteId = is_multisite() ? 1 : 0;
        $this->assertSame($siteId, $this->instance()->siteId());
    }

    public function testColumn()
    {
        $this->assertSame(Database::USER_COLUMN, $this->instance()->blockRecordColumn());
    }

    public function testBlockRecordColumnValue()
    {
        $this->assertSame(3, $this->instance(3)->blockRecordColumnValue());
    }

    public function testRootBlockType()
    {
        $this->assertSame('Awful.RootBlocks.User', $this->instance()->rootBlockType());
    }

    private function instance(int $userId = 1): User
    {
        $em = $this->createMock(EntityManager::class);
        return new User($em, $userId);
    }
}
