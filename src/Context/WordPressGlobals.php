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
    /** @var WP_Embed */
    private $embed;

    /** @var WP_Query */
    private $query;

    /** @var WP_Query */
    private $rewrite;

    /** @var WP */
    private $wp;

    /** @var WP_Widget_Factory */
    private $widget_factory;

    /** @var WP_Roles */
    private $roles;

    /** @var WP_Locale */
    private $locale;

    /** @var WP_Locale_Switcher */
    private $locale_switcher;

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
        if (!$this->widget_factory) {
            throw new UninitializedContextException();
        }
        return $this->widget_factory;
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
        if (!$this->locale_switcher) {
            throw new UninitializedContextException();
        }
        return $this->locale_switcher;
    }

    /**
     * @return void
     */
    private function listen(): void
    {
        add_action('mu_plugins_loaded', function () {
            global $wp_embed;

            $this->embed = $wp_embed;
        }, 1);

        add_action('setup_theme', function () {
            global $wp_query;
            global $wp_rewrite;
            global $wp;
            global $wp_widget_factory;
            global $wp_roles;

            $this->query = $wp_query;
            $this->rewrite = $wp_rewrite;
            $this->wp = $wp;
            $this->widget_factory = $wp_widget_factory;
            $this->roles = $wp_roles;
        }, 1);

        // This should run _before_ the listener in Awful.
        add_action('after_setup_theme', function () {
            global $wp_locale;
            global $wp_locale_switcher;

            $this->locale = $wp_locale;
            $this->locale_switcher = $wp_locale_switcher;
        }, 1);
    }
}
