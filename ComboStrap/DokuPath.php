<?php

namespace ComboStrap;


use http\Exception\RuntimeException;

/**
 * Class DokuPath
 * @package ComboStrap
 * A dokuwiki path has the same structure than a windows path
 * with a drive and a path
 *
 * The drive is just a local path on the local file system
 *
 * Dokuwiki knows only two drives ({@link DokuPath::PAGE_DRIVE} and {@link DokuPath::MEDIA_DRIVE}
 * but we have added a couple more such as the {@link DokuPath::COMBO_DRIVE combo resources}
 * and the {@link DokuPath::CACHE_DRIVE}
 *
 */
class DokuPath extends PathAbs
{

    const MEDIA_DRIVE = "media";
    const PAGE_DRIVE = "page";
    const UNKNOWN_DRIVE = "unknown";
    const NAMESPACE_SEPARATOR_DOUBLE_POINT = ":";

    // https://www.dokuwiki.org/config:useslash
    const NAMESPACE_SEPARATOR_SLASH = "/";

    const SEPARATORS = [self::NAMESPACE_SEPARATOR_DOUBLE_POINT, self::NAMESPACE_SEPARATOR_SLASH];

    /**
     * For whatever reason, dokuwiki uses also on windows
     * the linux separator
     */
    public const DIRECTORY_SEPARATOR = "/";
    public const SLUG_SEPARATOR = "-";


    /**
     * Dokuwiki has a file system that starts at a page and/or media
     * directory that depends on the used syntax.
     *
     * It's a little bit the same than as the icon library (we set it as library then)
     *
     * This parameters is an URL parameter
     * that permits to set an another one
     * when retrieving the file via HTTP
     * For now, there is only one value: {@link DokuPath::COMBO_DRIVE}
     */
    public const DRIVE_ATTRIBUTE = "drive";

    /**
     * The interwiki scheme that points to the
     * combo resources directory ie {@link DokuPath::COMBO_DRIVE}
     * ie
     *   combo>library:
     *   combo>image:
     */
    const COMBO_DRIVE = "combo";
    const CACHE_DRIVE = "cache";
    const DRIVES = [self::COMBO_DRIVE, self::CACHE_DRIVE, self::MEDIA_DRIVE];
    const PAGE_FILE_TXT_EXTENSION = ".txt";
    const REV_ATTRIBUTE = "rev";
    const CURRENT_PATH_CHARACTER = ".";
    const CURRENT_PARENT_PATH_CHARACTER = "..";
    const CANONICAL = "wiki-path";

    /**
     * @var string[]
     */
    private static $reservedWords;

    /**
     * @var string the path id passed to function (cleaned)
     */
    private $id;


    /**
     * @var string
     */
    private $drive;
    /**
     * @var string|null - ie mtime
     */
    private $rev;


    /**
     * The separator from the {@link DokuPath::getDrive()}
     */
    const DRIVE_SEPARATOR = ">";
    /**
     * @var string - the entered path (we use it for now to handle directory by adding a separator at the end)
     */
    private $path;

    /**
     * DokuPath constructor.
     *
     * A path for the Dokuwiki File System
     *
     * @param string $path - the dokuwiki absolute path (may not be relative but may be a namespace)
     * @param string $drive - the drive (media, page, combo) - same as in windows for the drive prefix (c, d, ...)
     * @param string|null $rev - the revision (mtime)
     *
     * Thee path should be a qualified/absolute path because in Dokuwiki, a link to a {@link PageFragment}
     * that ends with the {@link DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT} points to a start page
     * and not to a namespace. The qualification occurs in the transformation
     * from ref to page.
     *   For a page: in {@link MarkupRef::getInternalPage()}
     *   For a media: in the {@link MediaLink::createMediaLinkFromId()}
     * Because this class is mostly the file representation, it should be able to
     * represents also a namespace
     * @throws ExceptionNotFound
     */
    protected function __construct(string $path, string $drive, string $rev = null)
    {

        /**
         * Due to the fact that the request environment is set on the setup in test,
         * the path may be not normalized
         */
        $path = self::normalizeWikiPath($path);

        if (trim($path) === "") {
            $path = DokuPath::getRequestedPagePath()->toPathString();
        }

        /**
         * Relative Path ?
         */
        $this->path = $path;
        $firstCharacter = substr($path, 0, 1);
        if ($drive === self::PAGE_DRIVE && $firstCharacter !== DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            $parts = preg_split('/' . DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT . '/', $path);
            switch ($parts[0]) {
                case DokuPath::CURRENT_PATH_CHARACTER:
                    // delete the relative character
                    $parts = array_splice($parts, 1);
                    try {
                        $rootRelativePath = DokuPath::getCurrentPagePath();
                    } catch (ExceptionNotFound $e) {
                        // Root case: the relative path is in the root
                        // the root has no parent
                        LogUtility::error("The current relative path ({$this->path}) returns an error: {$e->getMessage()}",self::CANONICAL);
                        $rootRelativePath = DokuPath::createPagePathFromPath(DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT);
                    }
                    break;
                case DokuPath::CURRENT_PARENT_PATH_CHARACTER:
                    // delete the relative character
                    $parts = array_splice($parts, 1);
                    try {
                        $currentPagePath = DokuPath::getCurrentPagePath();
                        try {
                            $rootRelativePath = $currentPagePath->getParent();
                        } catch (ExceptionNotFound $e) {
                            LogUtility::error("The parent relative path ({$this->path}) returns an error: {$e->getMessage()}",self::CANONICAL);
                            $rootRelativePath = DokuPath::getCurrentPagePath();
                        }
                    } catch (ExceptionNotFound $e) {
                        LogUtility::error("The parent relative path ({$this->path}) returns an error: {$e->getMessage()}",self::CANONICAL);
                    }
                    break;
                default:
                    /**
                     * just a relative name path
                     * (ie hallo)
                     */
                    try {
                        $rootRelativePath = DokuPath::getCurrentPagePath();
                    } catch (ExceptionNotFound $e) {
                        LogUtility::error("The named relative path ({$this->path}) returns an error: {$e->getMessage()}",self::CANONICAL);
                        $rootRelativePath = DokuPath::createPagePathFromPath(DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT);
                    }
                    break;
            }
            // is relative directory path ?
            // ie ..: or .:
            $isRelativeDirectoryPath = false;
            $countParts = sizeof($parts);
            if ($countParts > 0 && $parts[$countParts - 1] === "") {
                $isRelativeDirectoryPath = true;
                $parts = array_splice($parts, 0, $countParts - 1);
            }
            foreach ($parts as $part) {
                $rootRelativePath = $rootRelativePath->resolve($part);
            }
            $absolutePathString = $rootRelativePath->toPathString();
            if ($isRelativeDirectoryPath && !DokuPath::isNamespacePath($absolutePathString)) {
                $absolutePathString = $absolutePathString . DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT;
            }
            $this->path = $absolutePathString;
        }


        /**
         * ACL check does not care about the type of id
         * https://www.dokuwiki.org/devel:event:auth_acl_check
         * https://github.com/splitbrain/dokuwiki/issues/3476
         *
         * We check if there is an extension
         * If this is the case, this is a media
         */
        if ($drive == self::UNKNOWN_DRIVE) {
            $lastPosition = StringUtility::lastIndexOf($path, ".");
            if ($lastPosition === FALSE) {
                $drive = self::PAGE_DRIVE;
            } else {
                $drive = self::MEDIA_DRIVE;
            }
        }
        $this->drive = $drive;

        /**
         * Path
         */
        if ($drive === self::PAGE_DRIVE) {
            $textExtension = self::PAGE_FILE_TXT_EXTENSION;
            $textExtensionLength = strlen($textExtension);
            $pathExtension = substr($this->path, -$textExtensionLength);
            if ($pathExtension === $textExtension) {
                // delete the extension, page does not have any extension
                $this->path = substr($this->path, 0, strlen($this->path) - $textExtensionLength);
            }
        }


        /**
         * We use interwiki to define the combo resources
         * (Internal use only)
         */
        $comboInterWikiScheme = "combo>";
        if (strpos($this->path, $comboInterWikiScheme) === 0) {
            $this->id = substr($this->path, strlen($comboInterWikiScheme));
            $this->drive = self::COMBO_DRIVE;
        } else {
            DokuPath::addRootSeparatorIfNotPresent($this->path);
            $this->id = DokuPath::toDokuwikiId($this->path);
        }


        $this->rev = $rev;

    }


    /**
     *
     * @param string $path
     * @param string|null $rev
     * @return DokuPath
     */
    public static function createPagePathFromPath(string $path, string $rev = null): DokuPath
    {
        try {
            return new DokuPath($path, DokuPath::PAGE_DRIVE, $rev);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Internal Error: The page drive is a known drive. Error: {$e->getMessage()}", self::CANONICAL, 1, $e);
        }
    }


    public static function createMediaPathFromPath($path, $rev = null): DokuPath
    {
        try {
            return new DokuPath($path, DokuPath::MEDIA_DRIVE, $rev);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("Internal Error: The drive should be known. Error: {$e->getMessage()}");
        }
    }

    /**
     * If the media may come from the
     * dokuwiki media or combo resources media,
     * you should use this function
     *
     * The constructor will determine the type based on
     * the id structure.
     * @param $id
     * @return DokuPath
     */
    public static function createFromUnknownRoot($id): DokuPath
    {
        return new DokuPath($id, DokuPath::UNKNOWN_DRIVE);
    }

    /**
     * @param $url - a URL path http://whatever/hello/my/lord (The canonical)
     * @return DokuPath - a dokuwiki Id hello:my:lord
     */
    public static function createFromUrl($url): DokuPath
    {
        // Replace / by : and suppress the first : because the global $ID does not have it
        $parsedQuery = parse_url($url, PHP_URL_QUERY);
        $parsedQueryArray = [];
        parse_str($parsedQuery, $parsedQueryArray);
        $queryId = 'id';
        if (array_key_exists($queryId, $parsedQueryArray)) {
            // Doku form (ie doku.php?id=)
            $id = $parsedQueryArray[$queryId];
        } else {
            // Slash form ie (/my/id)
            $urlPath = parse_url($url, PHP_URL_PATH);
            $id = substr(str_replace("/", ":", $urlPath), 1);
        }
        return self::createPagePathFromPath(":$id");
    }

    /**
     * Static don't ask why
     * @param $pathId
     * @return false|string
     */
    public static function getLastPart($pathId)
    {
        $endSeparatorLocation = StringUtility::lastIndexOf($pathId, DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT);
        if ($endSeparatorLocation === false) {
            $endSeparatorLocation = StringUtility::lastIndexOf($pathId, DokuPath::NAMESPACE_SEPARATOR_SLASH);
        }
        if ($endSeparatorLocation === false) {
            $lastPathPart = $pathId;
        } else {
            $lastPathPart = substr($pathId, $endSeparatorLocation + 1);
        }
        return $lastPathPart;
    }

    /**
     * @param $id
     * @return string
     * Return an path from a id
     */
    public static function IdToAbsolutePath($id)
    {
        if (is_null($id)) {
            LogUtility::msg("The id passed should not be null");
        }
        return DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT . $id;
    }

    public
    static function toDokuwikiId($path)
    {
        /**
         * Delete the first separator
         */
        if ($path[0] === DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            $path = substr($path, 1);
        }
        /**
         * Delete the extra separator from namespace
         */
        if (substr($path, -1) === DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            $path = substr($path, 0, strlen($path) - 1);
        }
        return $path;

    }

    public static function createMediaPathFromId($id, $rev = null): DokuPath
    {
        DokuPath::addRootSeparatorIfNotPresent($id);
        return self::createMediaPathFromPath($id, $rev);
    }


    public static function createPagePathFromId($id, $rev = null): DokuPath
    {
        DokuPath::addRootSeparatorIfNotPresent($id);
        return new DokuPath($id, self::PAGE_DRIVE, $rev);
    }

    /**
     * If the id does not have a root separator,
     * it's added (ie to transform an id to a path)
     * @param string $path
     */
    public static function addRootSeparatorIfNotPresent(string &$path)
    {
        $firstCharacter = substr($path, 0, 1);
        if (!in_array($firstCharacter, [DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, DokuPath::CURRENT_PATH_CHARACTER])) {
            $path = DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT . $path;
        }
    }

    /**
     * @param string $relativePath
     * @return string - a dokuwiki path (replacing the windows or linux path separator to the dokuwiki separator)
     */
    public static function toDokuWikiSeparator(string $relativePath): string
    {
        return preg_replace('/[\\\\\/]/', ":", $relativePath);
    }


    /**
     * @param $path - a manual path value
     * @return string -  a valid path
     */
    public static function toValidAbsolutePath($path): string
    {
        $path = cleanID($path);
        DokuPath::addRootSeparatorIfNotPresent($path);
        return $path;
    }

    /**
     */
    public static function createComboResource($dokuwikiId): DokuPath
    {
        return new DokuPath($dokuwikiId, self::COMBO_DRIVE);
    }

    /**
     */
    public static function createDokuPath($path, $drive, $rev = ''): DokuPath
    {
        return new DokuPath($path, $drive, $rev);
    }

    /**
     */
    public static function createPagePathFromGlobalId(): DokuPath
    {
        global $ID;
        if ($ID === null) {
            if (!PluginUtility::isTest()) {
                LogUtility::error("The markup fragment could not be identified (global wiki ID is null)");
            }
            $ID = DynamicRender::DEFAULT_SLOT_ID_FOR_TEST;
        }
        return DokuPath::createPagePathFromId($ID);
    }

    public static function createPagePathFromRequestedPage(): DokuPath
    {
        $pageId = PluginUtility::getRequestedWikiId();
        return DokuPath::createPagePathFromId($pageId);
    }

    /**
     * @throws ExceptionBadArgument - if the path is not a local path or is not in a known drive
     */
    public static function createFromPath(Path $path): DokuPath
    {
        if ($path instanceof DokuPath) {
            return $path;
        }
        if (!($path instanceof LocalPath)) {
            throw new ExceptionBadArgument("The path ($path) is not a local path and cannot be converted to a doku path");
        }
        $driveRoots = DokuPath::getDriveRoots();
        foreach ($driveRoots as $driveRoot => $drivePath) {

            try {
                $relativePath = $path->relativize($drivePath);
            } catch (ExceptionBadArgument $e) {
                /**
                 * May be a symlink link
                 */
                if (!is_link($drivePath->toPathString())) {
                    continue;
                }
                try {
                    $realPath = readlink($drivePath->toPathString());
                    $drivePath = LocalPath::createFromPath($realPath);
                    $relativePath = $path->relativize($drivePath);
                } catch (ExceptionBadArgument $e) {
                    // not a relative path
                    continue;
                }
            }
            $wikiPath = $relativePath->toPathString();
            if (FileSystems::isDirectory($path)) {
                DokuPath::addNamespaceEndSeparatorIfNotPresent($wikiPath);
            }
            return DokuPath::createDokuPath(":$wikiPath", $driveRoot);

        }
        throw new ExceptionBadArgument("The local path ($path) is not inside a wiki path drive");

    }

    /**
     * @return LocalPath[]
     */
    public static function getDriveRoots(): array
    {
        return [
            self::MEDIA_DRIVE => Site::getMediaDirectory(),
            self::PAGE_DRIVE => Site::getPageDirectory(),
            self::COMBO_DRIVE => DirectoryLayout::getComboResourcesDirectory(),
            self::CACHE_DRIVE => Site::getCacheDirectory()
        ];
    }

    /**
     *
     * Wiki path system cannot make the difference between a txt file
     * and a directory natively because there is no extension.
     *
     * ie `ns:name` is by default the file `ns:name.txt`
     *
     * To make this distinction, we add a `:` at the end
     *
     * TODO: May be ? We may also just check if the txt file exists
     *   and if not if the directory exists
     *
     * Also related {@link DokuPath::addNamespaceEndSeparatorIfNotPresent()}
     *
     * @param string $namespacePath
     * @return bool
     */
    public static function isNamespacePath(string $namespacePath): bool
    {
        if (substr($namespacePath, -1) !== DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            return false;
        }
        return true;

    }

    /**
     * @throws ExceptionBadSyntax
     */
    public static function checkNamespacePath(string $namespacePath)
    {
        if (!self::isNamespacePath($namespacePath)) {
            throw new ExceptionBadSyntax("The path ($namespacePath) is not a namespace path");
        }
    }

    /**
     * Add a end separator to the wiki path to pass the fact that this is a directory/namespace
     * See {@link DokuPath::isNamespacePath()} for more info
     *
     * @param string $namespaceAttribute
     * @return void
     */
    public static function addNamespaceEndSeparatorIfNotPresent(string &$namespaceAttribute)
    {
        if (substr($namespaceAttribute, -1) !== DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            $namespaceAttribute = $namespaceAttribute . DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT;
        }
    }

    /**
     * @param string $path - path or id
     * @param string $drive
     * @param string|null $rev
     * @return DokuPath
     */
    public static function create(string $path, string $drive, string $rev = null): DokuPath
    {
        return new DokuPath($path, $drive, $rev);
    }

    /**
     * @throws ExceptionNotFound
     */
    public static function getCurrentPagePath(): DokuPath
    {
        $requestedPath = self::getRequestedPagePath();
        try {
            $parent = $requestedPath->getParent();
        } catch (ExceptionNotFound $e) {
            throw new ExceptionNotFound("The current path ($requestedPath) does not have any parent.");
        }
        return $parent;
    }

    public static function getRequestedPagePath(): DokuPath
    {
        return DokuPath::createPagePathFromId(PluginUtility::getRequestedWikiId());
    }

    /**
     * Normalize a valid id
     * (ie from / to :)
     *
     * @param string $id
     * @return array|string|string[]
     *
     * This is not the same than {@link MarkupRef::normalizePath()}
     * because there is no relativity or any reserved character in a id
     *
     * as an {@link DokuPath::getWikiId() id} is a validated absolute path without root character
     */
    public static function normalizeWikiPath(string $id)
    {
        return str_replace(DokuPath::NAMESPACE_SEPARATOR_SLASH, DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, $id);
    }


    /**
     * The last part of the path
     * @throws ExceptionNotFound
     */
    public function getLastName(): string
    {
        /**
         * See also {@link noNSorNS}
         */
        $names = $this->getNames();
        $lastName = $names[sizeOf($names) - 1];
        if ($lastName === null) {
            throw new ExceptionNotFound("This path ($this) does not have any last name");
        }
        if ($this->getDrive() === self::PAGE_DRIVE) {
            return $lastName . self::PAGE_FILE_TXT_EXTENSION;
        }
        return $lastName;
    }

    public
    function getNames(): array
    {

        $actualNames = explode(self::NAMESPACE_SEPARATOR_DOUBLE_POINT, $this->getWikiId());

        /**
         * First element can be an empty string
         * Case of only one string without path separator
         * the first element returned is an empty string
         * Last element can be empty (namespace split, ie :ns:)
         */
        $names = [];
        foreach ($actualNames as $name) {
            /**
             * Don't use the {@link empty()} function
             * In the cache, we may have the directory '0'
             * and it's empty but is valid name
             */
            if ($name !== "") {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @return bool true if this id represents a page
     */
    public function isPage(): bool
    {

        if (
            $this->drive === self::PAGE_DRIVE
            &&
            !$this->isGlob()
        ) {
            return true;
        } else {
            return false;
        }

    }


    public function isGlob(): bool
    {
        /**
         * {@link search_universal} triggers ACL check
         * with id of the form :path:*
         * (for directory ?)
         */
        return StringUtility::endWiths($this->getWikiId(), ":*");
    }

    public
    function __toString()
    {
        return $this->toUriString();
    }

    /**
     *
     *
     * @return string - the id of dokuwiki is the absolute path
     * without the root separator (ie normalized)
     *
     * The index stores needs this value
     * And most of the function that are not links related
     * use this format (What fucked up is fucked up)
     *
     * The id is a validated absolute path without any root character.
     *
     * Heavily used inside Dokuwiki
     */
    public
    function getWikiId(): string
    {

        return $this->id;

    }

    public
    function getPath(): string
    {

        return $this->path;

    }


    public
    function getScheme(): string
    {

        return DokuFs::SCHEME;

    }

    /**
     * The dokuwiki revision value
     * as seen in the {@link basicinfo()} function
     * is the {@link File::getModifiedTime()} of the file
     *
     * Let op passing a revision to Dokuwiki will
     * make it search to the history
     * The actual file will then not be found
     *
     * @return string|null
     * @throws ExceptionNotFound
     */
    public
    function getRevision(): string
    {
        if ($this->rev === null) {
            throw new ExceptionNotFound("The rev was not set");
        }
        return $this->rev;
    }

    /**
     *
     * @throws ExceptionNotFound - if the revision is not set and the path does not exist
     */
    public function getRevisionOrDefault(){
        try {
            return $this->getRevision();
        } catch (ExceptionNotFound $e) {
            // same as $INFO['lastmod'];
            return FileSystems::getModifiedTime($this)->getTimestamp();
        }

    }


    /**
     * @return string
     *
     * This is the local absolute path WITH the root separator.
     * It's used in ref present in {@link MarkupRef link} or {@link MediaLink}
     * when creating test, otherwise the ref is considered as relative
     *
     *
     * Otherwise everywhere in Dokuwiki, they use the {@link DokuPath::getWikiId()} absolute value that does not have any root separator
     * and is absolute (internal index, function, ...)
     *
     */
    public
    function getAbsolutePath(): string
    {

        return $this->path;

    }

    /**
     * @return array the pages where the dokuwiki file (page or media) is used
     *   * backlinks for page
     *   * page with media for media
     */
    public
    function getReferencedBy(): array
    {
        $absoluteId = $this->getWikiId();
        if ($this->drive == self::MEDIA_DRIVE) {
            return idx_get_indexer()->lookupKey('relation_media', $absoluteId);
        } else {
            return idx_get_indexer()->lookupKey('relation_references', $absoluteId);
        }
    }


    /**
     * Return the path relative to the base directory
     * (ie $conf[basedir])
     * @return string
     */
    public
    function toRelativeFileSystemPath(): string
    {
        $relativeSystemPath = ".";
        if (!empty($this->getWikiId())) {
            $relativeSystemPath .= "/" . utf8_encodeFN(str_replace(':', '/', $this->getWikiId()));
        }
        return $relativeSystemPath;

    }

    public function isPublic(): bool
    {
        return $this->getAuthAclValue() >= AUTH_READ;
    }

    /**
     * @return int - An AUTH_ value for this page for the current logged user
     * See the file defines.php
     *
     */
    public function getAuthAclValue(): int
    {
        return auth_quickaclcheck($this->getWikiId());
    }


    public static function getReservedWords(): array
    {
        if (self::$reservedWords == null) {
            self::$reservedWords = array_merge(Url::RESERVED_WORDS, LocalPath::RESERVED_WINDOWS_CHARACTERS);
        }
        return self::$reservedWords;
    }


    /**
     * @return LocalPath
     * TODO: change it for a constructor on LocalPath
     */
    public function toLocalPath(): LocalPath
    {
        /**
         * File path
         */
        $filePathString = $this->path;
        $isNamespacePath = self::isNamespacePath($this->path);
        if ($isNamespacePath) {
            /**
             * Namespace
             * (Fucked up is fucked up)
             * We qualify for the namespace here
             * because there is no link or media for a namespace
             */
            global $conf;
            switch ($this->drive) {
                case self::MEDIA_DRIVE:
                    $localPath = LocalPath::createFromPath($conf['mediadir']);
                    break;
                case self::PAGE_DRIVE:
                    $localPath = LocalPath::createFromPath($conf['datadir']);
                    break;
                default:
                    $localPath = DokuPath::getDriveRoots()[$this->drive];
                    break;
            }

            foreach ($this->getNames() as $name) {
                $localPath = $localPath->resolve($name);
            }
            return $localPath;
        }

        // File
        switch ($this->drive) {
            case self::MEDIA_DRIVE:
                if (!empty($rev)) {
                    $filePathString = mediaFN($this->id, $rev);
                } else {
                    $filePathString = mediaFN($this->id);
                }
                break;
            case self::PAGE_DRIVE:
                /**
                 * TODO handle it to check if the id point to a directory
                 *   and returns the directory path in place if the txt file does not exist
                 */
                if (!empty($rev)) {
                    $filePathString = wikiFN($this->id, $rev);
                } else {
                    $filePathString = wikiFN($this->id);
                }
                break;
            default:
                $baseDirectory = DokuPath::getDriveRoots()[$this->drive];
                if ($baseDirectory === null) {
                    // We don't throw, the file will just not exist
                    // this is metadata
                    LogUtility::msg("The drive ($this->drive) is unknown, the local file system path could not be found");
                } else {
                    $filePath = $baseDirectory;
                    foreach ($this->getNames() as $name) {
                        $filePath = $filePath->resolve($name);
                    }
                    $filePathString = $filePath->toPathString();
                }
                break;
        }
        return LocalPath::createFromPath($filePathString);

    }

    /**
     * @return string - Returns the string representation of this path (to be able to use it in url)
     * To get the full string version see {@link DokuPath::toUriString()}
     */
    function toPathString(): string
    {
        return $this->path;
    }


    function toAbsolutePath(): Path
    {
        return new DokuPath($this->path, $this->drive, $this->rev);
    }

    /**
     * The parent path is a directory (namespace)
     * The root path throw an errors
     *
     * @return DokuPath
     * @throws ExceptionNotFound when the root
     */
    function getParent(): Path
    {
        /**
         * Same as {@link getNS()}
         */
        $names = $this->getNames();
        switch (sizeof($names)) {
            case 0:
                throw new ExceptionNotFound("No parent found");
            case 1:
                return new DokuPath(DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, $this->drive, $this->rev);
            default:
                $names = array_slice($names, 0, sizeof($names) - 1);
                $path = implode(DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, $names);
                /**
                 * Because DokuPath does not have the notion of extension
                 * if this is a page, we don't known if this is a directory
                 * or a page. To make the difference, we add a separator at the end
                 */
                $sep = self::NAMESPACE_SEPARATOR_DOUBLE_POINT;
                $path = "$sep$path$sep";
                return new DokuPath($path, $this->drive, $this->rev);
        }

    }

    function getMime(): ?Mime
    {
        if ($this->drive === self::PAGE_DRIVE) {
            return new Mime(Mime::PLAIN_TEXT);
        }
        return parent::getMime();

    }


    public
    function getDrive(): string
    {
        return $this->drive;
    }

    public
    function resolve(string $name): DokuPath
    {

        // Directory path have already separator at the end, don't add it
        if ($this->path[strlen($this->path) - 1] !== DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            $path = $this->path . DokuPath::NAMESPACE_SEPARATOR_DOUBLE_POINT . $name;
        } else {
            $path = $this->path . $name;
        }
        try {
            return new DokuPath($path, $this->getDrive());
        } catch (ExceptionNotFound $e) {
            throw new RuntimeException("Internal Error: The drive should already exist", 0, $e);
        }
    }


    function toUriString(): string
    {
        $driveSep = self::DRIVE_SEPARATOR;
        $uri = "{$this->getScheme()}://$this->drive$driveSep$this->id";
        if ($this->rev !== null) {
            $uri = "$uri?rev={$this->rev}";
        }
        return $uri;

    }

    function getUrl(): Url
    {
        return $this->toLocalPath()->getUrl();
    }

    function getHost(): string
    {
        return "localhost";
    }


}
