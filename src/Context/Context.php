<?php
namespace Awful;

use Awful\Models\Network;
use Awful\Models\Site;

class Context
{
    /** @var Network */
    private $network;

    /** @var Site */
    private $site;

    /** @var User */
    private $user;

    public function __construct(
        Awful $awful = null,
        Network $network = null,
        Site $site = null,
        User $user = null
    ) {
        // Allow injecting these things for testing.
        $this->network = $network;
        $this->site = $site;
        $this->user = $user;

        if (!$awful) {
            return;
        }

        // If Awful is injected, assume it's a real request.
        $awful->registerContextCallbacks(
            function (Site $site) {
                $this->site = $site;
            },
            function (User $site) {
                $this->user = $user;
            }
        );
    }

    public function network(): ?Network
    {
        return $this->network;
    }

    public function site(): Site
    {
        if (!$this->site) {
            throw new UninitializedContextException();
        }

        return $this->site;
    }

    public function user(): User
    {
        if (!$this->user) {
            throw new UninitializedContextException();
        }

        return $this->user;
    }
}
