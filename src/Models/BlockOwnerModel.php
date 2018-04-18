<?php
namespace Awful\Models;

interface BlockOwnerModel
{
    public function blockSet(): BlockSet;

    public function exists(): bool;
}
