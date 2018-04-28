<?php
namespace Awful\Context;

use WP;
use WP_Embed;
use WP_Locale;
use WP_Locale_Switcher;
use WP_Query;
use WP_Rewrite;
use WP_Roles;
use WP_Widget_Factory;

class WordPressGlobals
{
    /**
     * @var WP_Embed
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $embed;

    /**
     * @var WP_Query
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $query;

    /** @var WP_Rewrite
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $rewrite;

    /**
     * @var WP
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $wp;

    /**
     * @var WP_Widget_Factory
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $widgetFactory;

    /**
     * @var WP_Roles
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $roles;

    /**
     * @var WP_Locale
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $locale;

    /**
     * @var WP_Locale_Switcher
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $localeSwitcher;

    public function __construct(array $globals = null)
    {
        if ($globals === null) {
            // If none are passed explicitly assume we are in a real request.
            $this->listen();
            return;
        }

        foreach ($globals as $key => $global) {
            assert(property_exists($this, $key), "Expected a recognized global, given '$key'");
            $this->$key = $global;
        }
    }

    public function embed(): WP_Embed
    {
        if (!$this->embed) {
            throw new UninitializedContextException();
        }
        return $this->embed;
    }

    public function query(): WP_Query
    {
        if (!$this->query) {
            throw new UninitializedContextException();
        }
        return $this->query;
    }

    public function rewrite(): WP_Rewrite
    {
        if (!$this->rewrite) {
            throw new UninitializedContextException();
        }
        return $this->rewrite;
    }

    public function wp(): WP
    {
        if (!$this->wp) {
            throw new UninitializedContextException();
        }
        return $this->wp;
    }

    public function widgetFactory(): WP_Widget_Factory
    {
        if (!$this->widgetFactory) {
            throw new UninitializedContextException();
        }
        return $this->widgetFactory;
    }

    public function roles(): WP_Roles
    {
        if (!$this->roles) {
            throw new UninitializedContextException();
        }
        return $this->roles;
    }

    public function locale(): WP_Locale
    {
        if (!$this->locale) {
            throw new UninitializedContextException();
        }
        return $this->locale;
    }

    public function localeSwitcher(): WP_Locale_Switcher
    {
        if (!$this->localeSwitcher) {
            throw new UninitializedContextException();
        }
        return $this->localeSwitcher;
    }

    /**
     * @return void
     */
    private function listen(): void
    {
        add_action('mu_plugins_loaded', function (): void {
            $this->embed = $GLOBALS['wp_embed'];
        }, 1);

        add_action('setup_theme', function (): void {
            $this->query = $GLOBALS['wp_query'];
            $this->rewrite = $GLOBALS['wp_rewrite'];
            $this->wp = $GLOBALS['wp'];
            $this->widgetFactory = $GLOBALS['wp_widget_factory'];
            $this->roles = $GLOBALS['wp_roles'];
        }, 1);

        // This should run _before_ the listener in Awful.
        add_action('after_setup_theme', function (): void {
            $this->locale = $GLOBALS['wp_locale'];
            $this->localeSwitcher = $GLOBALS['wp_locale_switcher'];
        }, 1);
    }
}
