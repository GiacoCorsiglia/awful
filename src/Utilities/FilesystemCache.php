<?php
namespace Awful\Utilities;

use Awful\Models\Site;
use Awful\Theme;

final class FilesystemCache
{
    /** @var Theme */
    private $theme;

    /** @var Site */
    private $site;

    /** @var bool[] */
    private $mkdir_cache = [];

    public function __construct(Theme $theme, Site $site)
    {
        $this->theme = $theme;
        $this->site = $site;
    }

    public function getDirectoryPath(
        string $base_name,
        bool $per_theme = false,
        bool $per_site = false
    ): string {
        $path = untrailingslashit($this->theme->getCacheDirectory());
        if ($per_site) {
            $path = "{$this->site->getId()}/$path";
        }
        if ($per_theme) {
            $path = "{$this->theme->getName()}/$path";
        }
        if (!isset($this->mkdir_cache[$path])) {
            mkdir($path, 0755, true);
            $this->mkdir_cache[$path] = true;
        }
        return $path;
    }

    public function getFilePath(
        string $dir,
        string $base_name,
        bool $per_theme = false,
        bool $per_site = false
    ): string {
        $dir = $this->getDirectoryPath($dir);
        $name = $base_name;
        if ($per_site) {
            $name = "{$this->site->getId()}-$name";
        }
        if ($per_theme) {
            $name = "{$this->theme->getName()}-$name";
        }
        return "$dir/$name";
    }
}
