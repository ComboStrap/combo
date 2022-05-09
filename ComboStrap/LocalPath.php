<?php


namespace ComboStrap;

/**
 * Class LocalPath
 * @package ComboStrap
 * A local file system path
 */
class LocalPath extends PathAbs
{


    /**
     * The characters that cannot be in the path for windows
     * @var string[]
     */
    public const RESERVED_WINDOWS_CHARACTERS = ["\\", "/", ":", "*", "?", "\"", "<", ">", "|"];

    const RELATIVE_CURRENT = ".";
    const RELATIVE_PARENT = "..";
    const LINUX_SEPARATOR = "/";
    const WINDOWS_SEPARATOR = '\\';

    private $path;
    /**
     * @var mixed
     */
    private $sep = DIRECTORY_SEPARATOR;

    /**
     * LocalPath constructor.
     * @param $path - relative or absolute
     * @param null $sep - the directory separator - it permits to test to test linux path on windows, and vice-versa
     */
    public function __construct($path, $sep = null)
    {
        if ($sep != null) {
            $this->sep = $sep;
        }
        $this->path = self::normalizeToOsSeparator($path);
        if(is_link($this->path)){
            $this->path = readlink($this->path);
            $this->path = self::normalizeToOsSeparator($this->path);
        }
    }


    /**
     * @param string $filePath
     * @return LocalPath
     * @deprecated for {@link LocalPath::createFromPath()}
     */
    public static function create(string $filePath): LocalPath
    {
        return new LocalPath($filePath);
    }

    /**
     * @param $path
     * @return array|string|string[]
     *
     * For whatever reason, it seems that php/dokuwiki uses always the / separator on windows also
     * but not always (ie  https://www.php.net/manual/en/function.realpath.php output \ on windows)
     *
     * Because we want to be able to copy the path value and to be able to use
     * it directly, we normalize it to the OS separator at build time
     */
    private function normalizeToOsSeparator($path)
    {

        $directorySeparator = $this->getDirectorySeparator();
        if ($directorySeparator === self::WINDOWS_SEPARATOR) {
            return str_replace(self::LINUX_SEPARATOR, self::WINDOWS_SEPARATOR, $path);
        } else {
            return str_replace(self::WINDOWS_SEPARATOR, self::LINUX_SEPARATOR, $path);
        }
    }


    public static function createFromPath(string $string, string $sep = null): LocalPath
    {
        return new LocalPath($string, $sep);
    }

    function getScheme(): string
    {
        return LocalFs::SCHEME;
    }

    function getLastName()
    {
        $names = $this->getNames();
        $sizeof = sizeof($names);
        if ($sizeof === 0) {
            return null;
        }
        return $names[$sizeof - 1];

    }

    public function getExtension()
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    function getNames()
    {
        $directorySeparator = $this->getDirectorySeparator();
        return explode($directorySeparator, $this->path);
    }

    function getDokuwikiId()
    {
        throw new ExceptionRuntime("Not implemented");
    }


    function toString(): string
    {
        return $this->path;
    }

    public function getParent(): ?Path
    {
        $absolutePath = pathinfo($this->path, PATHINFO_DIRNAME);
        if (empty($absolutePath)) {
            return null;
        }
        return new LocalPath($absolutePath);
    }

    function toAbsolutePath(): Path
    {

        if ($this->isAbsolute()) {
            return $this;
        }

        return $this->toCanonicalPath();

    }


    /**
     * @throws ExceptionCompile
     */
    public function toDokuPath(): DokuPath
    {
        $driveRoots = DokuPath::getDriveRoots();
        foreach ($driveRoots as $driveRoot => $drivePath) {
            try {
                $relativePath = $this->relativize($drivePath);
                $wikiPath = $relativePath->toString();
                if ($wikiPath === self::RELATIVE_CURRENT) {
                    $wikiPath = "";
                }
                if (FileSystems::isDirectory($this)) {
                    $wikiPath .= DokuPath::PATH_SEPARATOR;
                }
                return DokuPath::createDokuPath($wikiPath, $driveRoot);
            } catch (ExceptionCompile $e) {
                // not a relative path
            }
        }
        throw new ExceptionCompile("The local path ($this) is not inside a wiki path drive");


    }

    public function resolve(string $name): LocalPath
    {

        $newPath = $this->toCanonicalPath()->toString() . $this->getDirectorySeparator() . $name;
        return self::createFromPath($newPath);

    }

    /**
     * @throws ExceptionCompile
     */
    public function relativize(LocalPath $localPath): LocalPath
    {
        $actualPath = $this->toCanonicalPath();
        $localPath = $localPath->toCanonicalPath();

        if (!(strpos($actualPath->toString(), $localPath->toString()) === 0)) {
            throw new ExceptionCompile("The path ($localPath) is not a parent path of the actual path ($actualPath)");
        }
        if ($actualPath->toString() === $localPath->toString()) {
            return LocalPath::createFromPath(self::RELATIVE_CURRENT);
        }
        $sepCharacter = 1; // delete the sep characters
        $relativePath = substr($actualPath->toString(), strlen($localPath->toString()) + $sepCharacter);
        $relativePath = str_replace($this->getDirectorySeparator(), DokuPath::PATH_SEPARATOR, $relativePath);
        return LocalPath::createFromPath($relativePath);

    }

    public function isAbsolute(): bool
    {
        /**
         * /
         * or a-z:\
         */
        if (preg_match("/^(\/|[a-z]:\\\\?).*/i", $this->path)) {
            return true;
        }
        return false;

    }

    /**
     * An absolute path may not be canonical
     * (ie windows short name or the path separator is not consistent (ie / in place of \ on windows)
     *
     * This function makes the path canonical meaning that two canonical path can be compared.
     */
    public function toCanonicalPath(): LocalPath
    {

        /**
         * realpath() is just a system/library call to actual realpath() function supported by OS.
         * real path handle also the windows name ie USERNAME~
         */
        $realPath = realpath($this->path);
        if ($realPath !== false) {
            return LocalPath::createFromPath($realPath);
        }

        /**
         * It returns false on on file that does not exists.
         * The suggestion on the realpath man page
         * is to look for an existing parent directory.
         * https://man7.org/linux/man-pages/man3/realpath.3.html
         */
        $parts = null;
        $isRoot = false;
        $counter = 0; // breaker
        $workingPath = $this->path;
        while ($realPath === false) {
            $counter++;
            $parent = dirname($workingPath);
            /**
             * From the doc: https://www.php.net/manual/en/function.dirname.php
             * dirname('.');    // Will return '.'.
             * dirname('/');    // Will return `\` on Windows and '/' on *nix systems.
             * dirname('\\');   // Will return `\` on Windows and '.' on *nix systems.
             * dirname('C:\\'); // Will return 'C:\' on Windows and '.' on *nix systems.
             * dirname('\');    // Will return `C:\` on Windows and ??? on *nix systems.
             */
            if (preg_match("/^(\.|\/|\\\\|[a-z]:\\\\)$/i", $parent)
                || $parent === $workingPath
                || $parent === "\\" // bug on regexp
            ) {
                $isRoot = true;
            }
            // root, no need to delete the last sep
            $lastSep = 1;
            if ($isRoot) {
                $lastSep = 0;
            }
            $parts[] = substr($workingPath, strlen($parent) + $lastSep);

            $realPath = realpath($parent);
            if ($isRoot) {
                break;
            }
            if ($counter > 200) {
                $message = "Bad absolute local path file ($this->path)";
                if (PluginUtility::isDevOrTest()) {
                    throw new ExceptionRuntime($message);
                } else {
                    LogUtility::msg($message);
                }
                return $this;
            }
            if ($realPath === false) {
                // loop
                $workingPath = $parent;
            }
        }
        if ($parts !== null) {
            if (!$isRoot) {
                $realPath .= $this->getDirectorySeparator();
            }
            $parts = array_reverse($parts);
            $realPath .= implode($this->getDirectorySeparator(), $parts);
        }
        return LocalPath::createFromPath($realPath);
    }

    public function getDirectorySeparator()
    {
        return $this->sep;
    }


}
