<?php
namespace AwfulExample;

class ExamplePlugin
{
    public function __construct()
    {
        add_action('admin_notices', [$this, 'printAdminNotice']);
    }

    public function printAdminNotice(): void
    {
        echo '<div class="notice notice-success"><p>Welcome to Awful.</p></div>';
    }
}
