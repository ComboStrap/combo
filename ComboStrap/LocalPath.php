<?php


namespace ComboStrap;

use ComboStrap\Web\Url;

/**
 * Class LocalPath
 * @package ComboStrap
 * A local file system path
 *
 * File protocol Uri:
 *
 * file://[HOST]/[PATH]
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
    const CANONICAL = "support";

    /**
     * @throws ExceptionBadArgument
     */
    public static function createFromUri($uri): LocalPath
    {
        if (strpos($uri, LocalFileSystem::SCHEME) !== 0) {
            throw new ExceptionBadArgument("$uri is not a local path uri");
        }
        return new LocalPath($uri);
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionCast
     */
    public static function createFromPathObject(Path $path): LocalPath
    {
        if ($path instanceof LocalPath) {
            return $path;
        }
        if ($path instanceof WikiPath) {
            return $path->toLocalPath();
        }
        throw new ExceptionBadArgument("The path is not a local path nor a wiki path, we can't transform it");
    }

    /**
     *
     * @throws ExceptionNotFound - if the env directory is not found
     */
    public static function createDesktopDirectory(): LocalPath
    {
        return LocalPath::createHomeDirectory()->resolve("Desktop");
    }


    public function toUriString(): string
    {
        return $this->getUrl()->toString();
    }

    private $path;
    /**
     * @var mixed
     */
    private $sep = DIRECTORY_SEPARATOR;

    private ?string $host = null;

    /**
     * LocalPath constructor.
     * @param string $path - relative or absolute, or a locale file uri
     * @param string|null $sep - the directory separator - it permits to test linux path on windows, and vice-versa
     */
    public function __construct(string $path, string $sep = null)
    {
        /**
         * php mon amour,
         * if we pass a {@link LocalPath}, no error,
         * it just pass the {@link PathAbs::__toString()}
         */
        if (strpos($path, LocalFileSystem::SCHEME) === 0) {
            try {
                $path = Url::createFromString($path)->getPath();
                LogUtility::errorIfDevOrTest("The path given as constructor should not be an uri or a path object");
            } catch (ExceptionBadArgument|ExceptionBadSyntax|ExceptionNotFound $e) {
                LogUtility::internalError("The uri path could not be created",self::CANONICAL, $e);
            }
        }
        if ($sep != null) {
            $this->sep = $sep;
        }
        // The network share windows/wiki styles with with two \\ and not //
        $networkShare = "\\\\";
        if (substr($path, 0, 2) === $networkShare) {
            // window share
            $pathWithoutNetworkShare = substr($path, 2);
            $pathWithoutNetworkShare = str_replace("\\", "/", $pathWithoutNetworkShare);
            [$this->host, $relativePath] = explode("/", $pathWithoutNetworkShare, 2);
            $this->path = "/$relativePath";
            return;
        }
        $this->path = self::normalizeToOsSeparator($path);
    }


    /**
     * @param string $filePath
     * @return LocalPath
     * @deprecated for {@link LocalPath::createFromPathString()}
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
        if ($path === self::RELATIVE_CURRENT || $path === self::RELATIVE_PARENT) {
            return realpath($path);
        }
        $directorySeparator = $this->getDirectorySeparator();
        if ($directorySeparator === self::WINDOWS_SEPARATOR) {
            return str_replace(self::LINUX_SEPARATOR, self::WINDOWS_SEPARATOR, $path);
        } else {
            return str_replace(self::WINDOWS_SEPARATOR, self::LINUX_SEPARATOR, $path);
        }
    }

    /**
     * @throws ExceptionNotFound
     */
    public static function createHomeDirectory(): LocalPath
    {
        $home = getenv("HOME");
        if ($home === false) {
            $home = getenv("USERPROFILE");
        }
        if ($home === false) {
            throw new ExceptionNotFound(" The home directory variable could not be found");
        }
        return LocalPath::createFromPathString($home);
    }


    public static function createFromPathString(string $string, string $sep = null): LocalPath
    {
        return new LocalPath($string, $sep);
    }

    function getScheme(): string
    {
        return LocalFileSystem::SCHEME;
    }

    function getLastName(): string
    {
        $names = $this->getNames();
        $sizeof = sizeof($names);
        if ($sizeof === 0) {
            throw new ExceptionNotFound("No last name for the path ($this)");
        }
        return $names[$sizeof - 1];

    }


    public function getExtension(): string
    {
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);
        if ($extension === "") {
            throw new ExceptionNotFound("No extension found for the path ($this)");
        }
        return $extension;
    }

    function getNames()
    {
        $directorySeparator = $this->getDirectorySeparator();
        return explode($directorySeparator, $this->path);
    }


    function toAbsoluteString(): string
    {
        return $this->path;
    }

    public function getParent(): Path
    {
        $absolutePath = pathinfo($this->path, PATHINFO_DIRNAME);
        if ($absolutePath === $this->path || empty($absolutePath)) {
            // the directory on windows of the root (ie C:\) is (C:\), yolo !
            throw new ExceptionNotFound("No parent");
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
     * @throws ExceptionBadArgument - if the path is not inside a drive
     */
    public function toWikiPath(): WikiPath
    {
        return WikiPath::createFromPathObject($this);
    }

    public function resolve(string $name): LocalPath
    {

        $newPath = $this->toCanonicalPath()->toAbsoluteString() . $this->getDirectorySeparator() . utf8_encodeFN($name);
        return self::createFromPathString($newPath);

    }

    /**
     * @throws ExceptionBadArgument - if the path cannot be relativized
     */
    public function relativize(LocalPath $localPath): LocalPath
    {
        $actualPath = $this->toCanonicalPath();
        $localPath = $localPath->toCanonicalPath();

        if (!(strpos($actualPath->toAbsoluteString(), $localPath->toAbsoluteString()) === 0)) {
            /**
             * May be a symlink link
             */
            if (is_link($this->path)) {
                $realPath = readlink($this->path);
                return LocalPath::createFromPathString($realPath)
                    ->relativize($localPath);
            }
            throw new ExceptionBadArgument("The path ($localPath) is not a parent path of the actual path ($actualPath)");
        }
        if ($actualPath->toAbsoluteString() === $localPath->toAbsoluteString()) {
            return LocalPath::createFromPathString("");
        }
        $sepCharacter = 1; // delete the sep characters
        $relativePath = substr($actualPath->toAbsoluteString(), strlen($localPath->toAbsoluteString()) + $sepCharacter);
        $relativePath = str_replace($this->getDirectorySeparator(), WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, $relativePath);
        return LocalPath::createFromPathString($relativePath);

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
     * This is also needed when you path a path string to a php function such as `clearstatcache`
     */
    public function toCanonicalPath(): LocalPath
    {

        /**
         * realpath() is just a system/library call to actual realpath() function supported by OS.
         * real path handle also the windows name ie USERNAME~
         */
        $realPath = realpath($this->path);
        if ($realPath !== false) {
            return LocalPath::createFromPathString($realPath);
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
        return LocalPath::createFromPathString($realPath);
    }

    public function getDirectorySeparator()
    {
        return $this->sep;
    }


    function getUrl(): Url
    {

        /**
         * file://host/path
         */
        $uri = LocalFileSystem::SCHEME . '://';
        try {
            // Windows share host
            $uri = "$uri{$this->getHost()}";
        } catch (ExceptionNotFound $e) {
            // ok
        }
        $pathNormalized = str_replace(self::WINDOWS_SEPARATOR, self::LINUX_SEPARATOR, $this->path);
        if ($pathNormalized[0] !== "/") {
            $uri = $uri . "/" . $pathNormalized;
        } else {
            $uri = $uri . $pathNormalized;
        }
        try {
            return Url::createFromString($uri);
        } catch (ExceptionBadSyntax|ExceptionBadArgument $e) {
            $message = "Local Uri Path has a bad syntax ($uri)";
            // should not happen
            LogUtility::internalError($message);
            throw new ExceptionRuntime($message);
        }

    }

    /**
     * @throws ExceptionNotFound
     */
    function getHost(): string
    {
        if ($this->host === null) {
            throw new ExceptionNotFound("No host. Localhost should be the default");
        }
        return $this->host;
    }
}
