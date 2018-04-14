<?php
namespace Awful\Templates;

/**
 * Wrapper for any template engine.
 */
interface TemplateEngine
{
    /**
     * Renders a template against the given context.
     *
     * @param string $template Template file path relative to the the current
     *                         theme's template directory.
     * @param array  $context  Associative array of template variables.
     *
     * @return string Rendered output.
     */
    public function render(string $template, array $context): string;
}
