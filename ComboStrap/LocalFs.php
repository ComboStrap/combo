<?php


namespace ComboStrap;


use DateTime;

class LocalFs implements FileSystem
{

    // same as the uri: ie local file os system
    public const SCHEME = "file";

    /**
     * @var LocalFs
     */
    private static $localFs;

    public static function getOrCreate(): LocalFs
    {
        if (self::$localFs === null) {
            self::$localFs = new LocalFs();
        }
        return self::$localFs;
    }

    function exists(Path $path): bool
    {
        return file_exists($path->toAbsolutePath()->toPathString());
    }

    /**
     * @param $path
     * @return string - textual content
     * @throws ExceptionNotFound - if the file does not exist
     */
    public function getContent($path): string
    {
        /**
         * Mime check
         */
        try {
            $mime = FileSystems::getMime($path);
            if (!$mime->isTextBased()) {
                LogUtility::error("This mime content ($mime) is not text based (for the path $path). We can't return a text.");
                return "";
            }
        } catch (ExceptionNotFound $e) {
            LogUtility::error("The mime is unknown for the path ($path). Trying to returning the content as text.");
        }
        $content = @file_get_contents($path->toAbsolutePath()->toPathString());
        if ($content === false) {
            // file does not exists
            throw new ExceptionNotFound("The file ($path) does not exists");
        }
        return $content;
    }

    /**
     * @throws ExceptionNotFound - if the file does not exist
     */
    public function getModifiedTime($path): DateTime
    {
        if (!self::exists($path)) {
            throw new ExceptionNotFound("Local File System Modified Time: The file ($path) does not exist");
        }
        return Iso8601Date::createFromTimestamp(filemtime($path->toAbsolutePath()->toPathString()))->getDateTime();
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getCreationTime(Path $path)
    {
        if (!$this->exists($path)) {
            throw new ExceptionNotFound("The path ($path) does not exists, no creation time");
        }
        $filePath = $path->toAbsolutePath()->toPathString();
        $timestamp = filectime($filePath);
        return Iso8601Date::createFromTimestamp($timestamp)->getDateTime();
    }

    public function delete(Path $path)
    {
        unlink($path->toAbsolutePath()->toPathString());
    }

    /**
     * @return false|int
     *@var LocalPath $path
     */
    public function getSize(Path $path)
    {
        return filesize($path->toAbsolutePath()->toPathString());
    }

    /**
     * @throws ExceptionCompile
     */
    public function createDirectory(Path $dirPath): Path
    {
        $result = mkdir($dirPath->toAbsolutePath()->toPathString(), $mode = 0770, $recursive = true);
        if ($result === false) {
            throw new ExceptionCompile("Unable to create the directory path ($dirPath)");
        }
        return $dirPath;
    }

    public function isDirectory(Path $path): bool
    {
        return is_dir($path->toAbsolutePath());
    }

    /**
     * @param LocalPath $path
     * @param string|null $type container / leaf (ie directory / file or namespace/page)
     * @return LocalPath[]
     */
    public function getChildren(Path $path, string $type = null): array
    {

        /**
         * Same as {@link scandir()}, they output
         * the current and parent relative directory (ie `.` and `..`)
         */
        $directoryHandle = opendir($path->toAbsolutePath());
        if (!$directoryHandle) return [];
        try {
            $localChildren = [];
            while (($fileName = readdir($directoryHandle)) !== false) {
                if (in_array($fileName, [LocalPath::RELATIVE_CURRENT, LocalPath::RELATIVE_PARENT])) {
                    continue;
                }
                $childPath = $path->resolve($fileName);
                if ($type === null) {
                    $localChildren[] = $childPath;
                    continue;
                }
                /**
                 * Filter is not null, filter
                 */
                switch ($type) {
                    case FileSystems::CONTAINER:
                        if (FileSystems::isDirectory($childPath)) {
                            $localChildren[] = $childPath;
                        }
                        break;
                    case FileSystems::LEAF:
                        if (!FileSystems::isDirectory($childPath)) {
                            $localChildren[] = $childPath;
                        }
                        break;
                    default:
                        LogUtility::internalError("The type of file ($type) is unknown. It should be `" . FileSystems::CONTAINER . "` or `" . FileSystems::LEAF . "`");
                        $localChildren[] = $childPath;
                }
            }
            return $localChildren;
        } finally {
            closedir($directoryHandle);
        }

    }

    /**
     * @param LocalPath $path
     * @param string $lastFullName
     * @return Path
     * @throws ExceptionNotFound
     */
    public function closest(Path $path, string $lastFullName): Path
    {
        if(FileSystems::isDirectory($path)) {
            $closest = $path->resolve($lastFullName);
            if (FileSystems::exists($closest)) {
                return $closest;
            }
        }
        $currentPath = $path;
        while (($parent = $currentPath->getParent()) !== null) {
            $closest = $parent->resolve($lastFullName);
            if (FileSystems::exists($closest)) {
                return $closest;
            }
            $currentPath = $parent;
        }
        throw new ExceptionNotFound("No closest was found for the file name ($lastFullName) from the path ($path)");
    }

    /**
     * @param LocalPath $path
     * @return void
     */
    public function createRegularFile(Path $path)
    {
        touch($path->toPathString());
    }

    public function setContent(Path $path, string $content)
    {
        io_saveFile($path->toPathString(), $content, false);
    }

}
