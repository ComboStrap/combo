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
     * For whatever reason, it seems that php uses always the / separator on windows also
     * but not always (ie  https://www.php.net/manual/en/function.realpath.php output \ on windows)
     *
     * Because we want to be able to copy the path and to be able to use
     * it directly, we {@link LocalPath::normalizedToOs() normalize} it to the OS separator
     * at build time
     */
    private const PHP_SYSTEM_DIRECTORY_SEPARATOR = DIRECTORY_SEPARATOR;

    /**
     * The characters that cannot be in the path for windows
     * @var string[]
     */
    public const RESERVED_WINDOWS_CHARACTERS = ["\\", "/", ":", "*", "?", "\"", "<", ">", "|"];

    private $path;

    /**
     * LocalPath constructor.
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = $this->normalizedToOs($path);
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

    public static function createFromPath(string $string): LocalPath
    {
        return new LocalPath($string);
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
        throw new ExceptionComboRuntime("Not implemented");
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
        /**
         * TODO: because we use realpath in {@link LocalPath::normalizedToOs()} used in the constructor
         *   the path is always absolute, not needed ?
         */
        $path = realpath($this->path);
        if ($path !== false) {
            // Path return false when the file does not exist
            return new LocalPath($path);
        }
        return $this;

    }

    /**
     * @return string
     */
    private function getDirectorySeparator(): string
    {
        $directorySeparator = self::PHP_SYSTEM_DIRECTORY_SEPARATOR;
        if (
            $directorySeparator === '\\'
            &&
            strpos($this->path, "/") !== false
        ) {
            $directorySeparator = "/";
        }
        return $directorySeparator;
    }


    /**
     * @throws ExceptionCombo
     */
    public function toDokuPath(): DokuPath
    {
        $driveRoots = DokuPath::getDriveRoots();
        foreach ($driveRoots as $driveRoot => $drivePath) {
            try {
                $relativePath = $this->relativize($drivePath);
                return DokuPath::createDokuPath($relativePath->toString(), $driveRoot);
            } catch (ExceptionCombo $e) {
                // not a relative path
            }

        }
        throw new ExceptionCombo("The local path ($this) is not inside a doku path drive");


    }

    public function resolve(string $name): LocalPath
    {

        $newPath = $this->path . self::PHP_SYSTEM_DIRECTORY_SEPARATOR . $name;
        if ($this->path[strlen($this->path) - 1] === self::PHP_SYSTEM_DIRECTORY_SEPARATOR) {
            $newPath = $this->path . $name;
        }
        return self::create($newPath);

    }

    /**
     * @throws ExceptionCombo
     */
    private function relativize(LocalPath $localPath): LocalPath
    {
        if (!(strpos($this->toString(), $localPath->toString()) === 0)) {
            throw new ExceptionCombo("The path ($localPath) is not a parent path of the actual path ($this)");
        }
        $sepCharacter = 1; // delete the sep characters
        $relativePath = substr($this->toString(), strlen($localPath->toString()) + $sepCharacter);
        $relativePath = str_replace(self::PHP_SYSTEM_DIRECTORY_SEPARATOR, DokuPath::PATH_SEPARATOR, $relativePath);
        return LocalPath::create($relativePath);
    }

    private function normalizedToOs($path)
    {

        /**
         * realpath() is just a system/library call to actual realpath() function supported by OS.
         * real path handle also the windows name ie USERNAME~
         *
         */
        $realPath = realpath($path);
        $parts = null;
        $isRoot = false;
        while ($realPath === false) {
            /**
             * It returns false on on file that does not exists.
             * The suggestion on the realpath man page
             * is to look for an existing parent directory.
             * https://man7.org/linux/man-pages/man3/realpath.3.html
             */
            $parent = dirname($path);
            /**
             * From the doc: https://www.php.net/manual/en/function.dirname.php
             * dirname('.');    // Will return '.'.
             * dirname('/');    // Will return `\` on Windows and '/' on *nix systems.
             * dirname('\\');   // Will return `\` on Windows and '.' on *nix systems.
             * dirname('C:\\'); // Will return 'C:\' on Windows and '.' on *nix systems.
             * dirname('\');    // Will return `C:\` on Windows and ??? on *nix systems.
             */
            if (preg_match("/^\.|\\\\|[a-z]:\\\\$/i", $parent)) {
                $isRoot = true;
            }
            // root, no need to delete the last sep
            $lastSep = 1;
            if ($isRoot) {
                $lastSep = 0;
            }
            $parts[] = substr($path, strlen($parent) + $lastSep);

            $realPath = realpath($parent);
            if ($isRoot) {
                break;
            }
            if($realPath===false) {
                // loop
                $path = $parent;
            }
        }
        if ($parts !== null) {
            if (!$isRoot) {
                $realPath .= self::PHP_SYSTEM_DIRECTORY_SEPARATOR;
            }
            $parts = array_reverse($parts);
            $realPath .= implode(self::PHP_SYSTEM_DIRECTORY_SEPARATOR, $parts);
        }
        return $realPath;
    }


}
