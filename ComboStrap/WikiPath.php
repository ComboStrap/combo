<?php

namespace ComboStrap;


use ComboStrap\Web\Url;

/**
 * Class DokuPath
 * @package ComboStrap
 * A dokuwiki path has the same structure than a windows path with a drive and a path
 *
 * The drive being a local path on the local file system
 *
 * Ultimately, this path is the application path and should be used everywhere.
 * * for users input (ie in a link markup such as media and page link)
 * * for output (ie creating the id for the url)
 *
 * Dokuwiki knows only two drives ({@link WikiPath::MARKUP_DRIVE} and {@link WikiPath::MEDIA_DRIVE}
 * but we have added a couple more such as the {@link WikiPath::COMBO_DRIVE combo resources}
 * and the {@link WikiPath::CACHE_DRIVE} to be able to serve resources
 *
 * TODO: because all {@link LocalPath} has at minium a drive (ie C:,D:, E: for windows or \ for linux)
 *    A Wiki Path can be just a wrapper around every local path)
 *    The {@link LocalPath::toWikiPath()} should not throw then but as not all drive
 *    may be public, we need to add a drive functionality to get this information.
 */
class WikiPath extends PathAbs
{

    const MEDIA_DRIVE = "media";
    const MARKUP_DRIVE = "markup";
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
     * For now, there is only one value: {@link WikiPath::COMBO_DRIVE}
     */
    public const DRIVE_ATTRIBUTE = "drive";

    /**
     * The interwiki scheme that points to the
     * combo resources directory ie {@link WikiPath::COMBO_DRIVE}
     * ie
     *   combo>library:
     *   combo>image:
     */
    const COMBO_DRIVE = "combo";
    /**
     * The home directory for all themes
     */
    const COMBO_DATA_THEME_DRIVE = "combo-theme";
    const CACHE_DRIVE = "cache";
    const MARKUP_DEFAULT_TXT_EXTENSION = "txt";
    const MARKUP_MD_TXT_EXTENSION = "md";
    const REV_ATTRIBUTE = "rev";
    const CURRENT_PATH_CHARACTER = ".";
    const CURRENT_PARENT_PATH_CHARACTER = "..";
    const CANONICAL = "wiki-path";
    const ALL_MARKUP_EXTENSIONS = [self::MARKUP_DEFAULT_TXT_EXTENSION, self::MARKUP_MD_TXT_EXTENSION];


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
     * The separator from the {@link WikiPath::getDrive()}
     */
    const DRIVE_SEPARATOR = ">";
    /**
     * @var string - the absolute path (we use it for now to handle directory by adding a separator at the end)
     */
    protected $absolutePath;

    /**
     * DokuPath constructor.
     *
     * A path for the Dokuwiki File System
     *
     * @param string $path - the path (may be relative)
     * @param string $drive - the drive (media, page, combo) - same as in windows for the drive prefix (c, d, ...)
     * @param string|null $rev - the revision (mtime)
     *
     * Thee path should be a qualified/absolute path because in Dokuwiki, a link to a {@link MarkupPath}
     * that ends with the {@link WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT} points to a start page
     * and not to a namespace. The qualification occurs in the transformation
     * from ref to page.
     *   For a page: in {@link MarkupRef::getInternalPage()}
     *   For a media: in the {@link MediaLink::createMediaLinkFromId()}
     * Because this class is mostly the file representation, it should be able to
     * represents also a namespace
     */
    protected function __construct(string $path, string $drive, string $rev = null)
    {

        $executionContext = ExecutionContext::getActualOrCreateFromEnv();

        /**
         * Due to the fact that the request environment is set on the setup in test,
         * the path may be not normalized
         */
        $path = self::normalizeWikiPath($path);

        if (trim($path) === "") {
            try {
                $path = WikiPath::getContextPath()->toAbsoluteId();
            } catch (ExceptionNotFound $e) {
                throw new ExceptionRuntimeInternal("The context path is unknwon. The empty path string needs it.");
            }
        }

        /**
         * Relative Path ?
         */
        $this->absolutePath = $path;
        $firstCharacter = substr($path, 0, 1);
        if ($drive === self::MARKUP_DRIVE && $firstCharacter !== WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            $parts = preg_split('/' . WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT . '/', $path);
            switch ($parts[0]) {
                case WikiPath::CURRENT_PATH_CHARACTER:
                    // delete the relative character
                    $parts = array_splice($parts, 1);
                    try {
                        $rootRelativePath = $executionContext->getContextNamespacePath();
                    } catch (ExceptionNotFound $e) {
                        // Root case: the relative path is in the root
                        // the root has no parent
                        LogUtility::error("The current relative path ({$this->absolutePath}) returns an error: {$e->getMessage()}", self::CANONICAL);
                        $rootRelativePath = WikiPath::createMarkupPathFromPath(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT);
                    }
                    break;
                case WikiPath::CURRENT_PARENT_PATH_CHARACTER:
                    // delete the relative character
                    $parts = array_splice($parts, 1);

                    $currentPagePath = $executionContext->getContextNamespacePath();
                    try {
                        $rootRelativePath = $currentPagePath->getParent();
                    } catch (ExceptionNotFound $e) {
                        // No parent
                        $rootRelativePath = $executionContext->getContextNamespacePath();
                    }

                    break;
                default:
                    /**
                     * just a relative name path
                     * (ie hallo)
                     */
                    $rootRelativePath = $executionContext->getContextNamespacePath();
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
            $absolutePathString = $rootRelativePath->getAbsolutePath();
            if ($isRelativeDirectoryPath && !WikiPath::isNamespacePath($absolutePathString)) {
                $absolutePathString = $absolutePathString . WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT;
            }
            $this->absolutePath = $absolutePathString;
        }


        /**
         * ACL check does not care about the type of id
         * https://www.dokuwiki.org/devel:event:auth_acl_check
         * https://github.com/splitbrain/dokuwiki/issues/3476
         *
         * We check if there is an extension
         * If this is the case, this is a media
         */
        if ($drive === self::UNKNOWN_DRIVE) {
            $lastPosition = StringUtility::lastIndexOf($path, ".");
            if ($lastPosition === FALSE) {
                $drive = self::MARKUP_DRIVE;
            } else {
                $drive = self::MEDIA_DRIVE;
            }
        }
        $this->drive = $drive;


        /**
         * We use interwiki to define the combo resources
         * (Internal use only)
         */
        $comboInterWikiScheme = "combo>";
        if (strpos($this->absolutePath, $comboInterWikiScheme) === 0) {
            $pathPart = substr($this->absolutePath, strlen($comboInterWikiScheme));
            $this->id = $this->toDokuWikiIdDriveContextual($pathPart);
            $this->drive = self::COMBO_DRIVE;
        } else {
            WikiPath::addRootSeparatorIfNotPresent($this->absolutePath);
            $this->id = $this->toDokuWikiIdDriveContextual($this->absolutePath);
        }


        $this->rev = $rev;

    }


    /**
     * For a Markup drive path, a file path should have an extension
     * if it's not a namespace
     *
     * This function checks that
     *
     * @param string $parameterPath - the path in a wiki form that may be relative - if the path is blank, it's the current markup (the requested markup)
     * @param string|null $rev - the revision (ie timestamp in number format)
     * @return WikiPath - the wiki path
     * @throws ExceptionBadArgument - if a relative path is given and the context path does not have any parent
     */
    public static function createMarkupPathFromPath(string $parameterPath, string $rev = null): WikiPath
    {
        $executionContext = ExecutionContext::getActualOrCreateFromEnv();

        if ($parameterPath == "") {
            return $executionContext->getContextPath();
        }
        if (WikiPath::isNamespacePath($parameterPath)) {

            if ($parameterPath[0] !== self::CURRENT_PATH_CHARACTER) {
                /**
                 * Not a relative path
                 */
                return new WikiPath($parameterPath, self::MARKUP_DRIVE, $rev);
            }
            /**
             * A relative path
             */
            $contextPath = $executionContext->getContextPath();
            if ($parameterPath === self::CURRENT_PARENT_PATH_CHARACTER . self::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
                /**
                 * ie processing `..:`
                 */
                try {
                    return $contextPath->getParent()->getParent();
                } catch (ExceptionNotFound $e) {
                    throw new ExceptionBadArgument("The context path ($contextPath) does not have a grand parent, therefore the relative path ($parameterPath) is invalid.", $e);
                }
            }
            /**
             * ie processing `.:`
             */
            try {
                return $contextPath->getParent();
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("A context path is a page and should therefore have a parent", $e);
            }

        }

        /**
         * Default Path
         * (we add the txt extension if not present)
         */
        $defaultPath = $parameterPath;
        $lastName = $parameterPath;
        $lastSeparator = strrpos($parameterPath, self::NAMESPACE_SEPARATOR_DOUBLE_POINT);
        if ($lastSeparator !== false) {
            $lastName = substr($parameterPath, $lastSeparator);
        }
        $lastPoint = strpos($lastName, ".");
        if ($lastPoint === false) {
            $defaultPath = $defaultPath . '.' . self::MARKUP_DEFAULT_TXT_EXTENSION;
        } else {
            /**
             * Case such as file `1.22`
             */
            $parameterPathExtension = substr($lastName, $lastPoint + 1);
            if (!in_array($parameterPathExtension, self::ALL_MARKUP_EXTENSIONS)) {
                $defaultPath = $defaultPath . '.' . self::MARKUP_DEFAULT_TXT_EXTENSION;
            }
        }
        $defaultWikiPath = new WikiPath($defaultPath, self::MARKUP_DRIVE, $rev);
        if (FileSystems::exists($defaultWikiPath)) {
            return $defaultWikiPath;
        }

        /**
         * Markup extension (Markdown, ...)
         */
        if (!isset($parameterPathExtension)) {
            foreach (self::ALL_MARKUP_EXTENSIONS as $markupExtension) {
                if ($markupExtension == self::MARKUP_DEFAULT_TXT_EXTENSION) {
                    continue;
                }
                $markupWikiPath = new WikiPath($parameterPath . '.' . $markupExtension, self::MARKUP_DRIVE, $rev);
                if (FileSystems::exists($markupWikiPath)) {
                    return $markupWikiPath;
                }
            }
        }

        /**
         * Return the non-existen default wiki path
         */
        return $defaultWikiPath;

    }


    public
    static function createMediaPathFromPath($path, $rev = null): WikiPath
    {
        return new WikiPath($path, WikiPath::MEDIA_DRIVE, $rev);
    }

    /**
     * If the media may come from the
     * dokuwiki media or combo resources media,
     * you should use this function
     *
     * The constructor will determine the type based on
     * the id structure.
     * @param $id
     * @return WikiPath
     */
    public
    static function createFromUnknownRoot($id): WikiPath
    {
        return new WikiPath($id, WikiPath::UNKNOWN_DRIVE);
    }

    /**
     * @param $url - a URL path http://whatever/hello/my/lord (The canonical)
     * @return WikiPath - a dokuwiki Id hello:my:lord
     * @deprecated for {@link FetcherPage::createPageFragmentFetcherFromUrl()}
     */
    public
    static function createFromUrl($url): WikiPath
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
        return self::createMarkupPathFromPath(":$id");
    }

    /**
     * Static don't ask why
     * @param $pathId
     * @return false|string
     */
    public
    static function getLastPart($pathId)
    {
        $endSeparatorLocation = StringUtility::lastIndexOf($pathId, WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT);
        if ($endSeparatorLocation === false) {
            $endSeparatorLocation = StringUtility::lastIndexOf($pathId, WikiPath::NAMESPACE_SEPARATOR_SLASH);
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
    public
    static function IdToAbsolutePath($id)
    {
        if (is_null($id)) {
            LogUtility::msg("The id passed should not be null");
        }
        return WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT . $id;
    }

    function toDokuWikiIdDriveContextual($path): string
    {
        /**
         * Delete the first separator
         */
        $id = self::removeRootSepIfPresent($path);

        /**
         * If this is a markup, we delete the txt extension if any
         */
        if ($this->getDrive() === self::MARKUP_DRIVE) {
            StringUtility::rtrim($id, '.' . self::MARKUP_DEFAULT_TXT_EXTENSION);
        }
        return $id;

    }

    public
    static function createMediaPathFromId($id, $rev = null): WikiPath
    {
        WikiPath::addRootSeparatorIfNotPresent($id);
        return self::createMediaPathFromPath($id, $rev);
    }

    public static function getComboCustomThemeHomeDirectory(): WikiPath
    {
        return new WikiPath(self::NAMESPACE_SEPARATOR_DOUBLE_POINT, self::COMBO_DATA_THEME_DRIVE);
    }

    /**
     * @throws ExceptionBadArgument
     */
    public
    static function createFromUri(string $uri): WikiPath
    {

        $schemeQualified = WikiFileSystem::SCHEME . "://";
        $lengthSchemeQualified = strlen($schemeQualified);
        $uriScheme = substr($uri, 0, $lengthSchemeQualified);
        if ($uriScheme !== $schemeQualified) {
            throw new ExceptionBadArgument("The uri ($uri) is not a wiki uri");
        }
        $uriWithoutScheme = substr($uri, $lengthSchemeQualified);
        $locationQuestionMark = strpos($uriWithoutScheme, "?");
        if ($locationQuestionMark === false) {
            $pathAndDrive = $uriWithoutScheme;
            $rev = '';
        } else {
            $pathAndDrive = substr($uriWithoutScheme, 0, $locationQuestionMark);
            $query = substr($uriWithoutScheme, $locationQuestionMark + 1);
            parse_str($query, $queryKeys);
            $queryKeys = new ArrayCaseInsensitive($queryKeys);
            $rev = $queryKeys['rev'];
        }
        $locationGreaterThan = strpos($pathAndDrive, ">");
        if ($locationGreaterThan === false) {
            $path = $pathAndDrive;
            $locationLastPoint = strrpos($pathAndDrive, ".");
            if ($locationLastPoint === false) {
                $drive = WikiPath::MARKUP_DRIVE;
            } else {
                $extension = substr($pathAndDrive, $locationLastPoint + 1);
                if (in_array($extension, WikiPath::ALL_MARKUP_EXTENSIONS)) {
                    $drive = WikiPath::MARKUP_DRIVE;
                } else {
                    $drive = WikiPath::MEDIA_DRIVE;
                }
            }
        } else {
            $drive = substr($pathAndDrive, 0, $locationGreaterThan);
            $path = substr($pathAndDrive, $locationGreaterThan + 1);
        }
        return new WikiPath(":$path", $drive, $rev);
    }


    public
    static function createMarkupPathFromId($id, $rev = null): WikiPath
    {
        if (strpos($id, WikiFileSystem::SCHEME . "://") !== false) {
            return WikiPath::createFromUri($id);
        }
        WikiPath::addRootSeparatorIfNotPresent($id);
        return self::createMarkupPathFromPath($id);
    }

    /**
     * If the id does not have a root separator,
     * it's added (ie to transform an id to a path)
     * @param string $path
     */
    public
    static function addRootSeparatorIfNotPresent(string &$path)
    {
        $firstCharacter = substr($path, 0, 1);
        if (!in_array($firstCharacter, [WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, WikiPath::CURRENT_PATH_CHARACTER])) {
            $path = WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT . $path;
        }
    }

    /**
     * @param string $relativePath
     * @return string - a dokuwiki path (replacing the windows or linux path separator to the dokuwiki separator)
     */
    public
    static function toDokuWikiSeparator(string $relativePath): string
    {
        return preg_replace('/[\\\\\/]/', ":", $relativePath);
    }


    /**
     * @param $path - a manual path value
     * @return string -  a valid path
     */
    public
    static function toValidAbsolutePath($path): string
    {
        $path = cleanID($path);
        WikiPath::addRootSeparatorIfNotPresent($path);
        return $path;
    }

    /**
     */
    public
    static function createComboResource($stringPath): WikiPath
    {
        return new WikiPath($stringPath, self::COMBO_DRIVE);
    }


    /**
     * @param $path - relative or absolute path
     * @param $drive - the drive
     * @param string $rev - the revision
     * @return WikiPath
     */
    public
    static function createWikiPath($path, $drive, string $rev = ''): WikiPath
    {
        return new WikiPath($path, $drive, $rev);
    }

    /**
     * The executing markup
     * @throws ExceptionNotFound
     */
    public
    static function createExecutingMarkupWikiPath(): WikiPath
    {
        return ExecutionContext::getActualOrCreateFromEnv()
            ->getExecutingWikiPath();

    }


    /**
     * @throws ExceptionNotFound
     */
    public
    static function createRequestedPagePathFromRequest(): WikiPath
    {
        return ExecutionContext::getActualOrCreateFromEnv()->getRequestedPath();
    }

    /**
     * @throws ExceptionBadArgument - if the path is not a local path or is not in a known drive
     */
    public
    static function createFromPathObject(Path $path): WikiPath
    {
        if ($path instanceof WikiPath) {
            return $path;
        }
        if (!($path instanceof LocalPath)) {
            throw new ExceptionBadArgument("The path ($path) is not a local path and cannot be converted to a wiki path");
        }
        $driveRoots = WikiPath::getDriveRoots();

        foreach ($driveRoots as $driveRoot => $drivePath) {

            try {
                $relativePath = $path->relativize($drivePath);
            } catch (ExceptionBadArgument $e) {
                /**
                 * The drive may be a symlink link
                 * (not the path)
                 */
                if (!$drivePath->isSymlink()) {
                    continue;
                }
                try {
                    $drivePath = $drivePath->toCanonicalAbsolutePath();
                    $relativePath = $path->relativize($drivePath);
                } catch (ExceptionBadArgument $e) {
                    // not a relative path
                    continue;
                }
            }
            $wikiId = $relativePath->toAbsoluteId();
            if (FileSystems::isDirectory($path)) {
                WikiPath::addNamespaceEndSeparatorIfNotPresent($wikiId);
            }
            WikiPath::addRootSeparatorIfNotPresent($wikiId);
            return WikiPath::createWikiPath($wikiId, $driveRoot);

        }
        throw new ExceptionBadArgument("The local path ($path) is not inside a wiki path drive");

    }

    /**
     * @return LocalPath[]
     */
    public
    static function getDriveRoots(): array
    {
        return [
            self::MEDIA_DRIVE => Site::getMediaDirectory(),
            self::MARKUP_DRIVE => Site::getPageDirectory(),
            self::COMBO_DRIVE => DirectoryLayout::getComboResourcesDirectory(),
            self::COMBO_DATA_THEME_DRIVE => Site::getDataDirectory()->resolve("combo")->resolve("theme"),
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
     * Also related {@link WikiPath::addNamespaceEndSeparatorIfNotPresent()}
     *
     * @param string $namespacePath
     * @return bool
     */
    public
    static function isNamespacePath(string $namespacePath): bool
    {
        if (substr($namespacePath, -1) !== WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            return false;
        }
        return true;

    }

    /**
     * @throws ExceptionBadSyntax
     */
    public
    static function checkNamespacePath(string $namespacePath)
    {
        if (!self::isNamespacePath($namespacePath)) {
            throw new ExceptionBadSyntax("The path ($namespacePath) is not a namespace path");
        }
    }

    /**
     * Add a end separator to the wiki path to pass the fact that this is a directory/namespace
     * See {@link WikiPath::isNamespacePath()} for more info
     *
     * @param string $namespaceAttribute
     * @return void
     */
    public
    static function addNamespaceEndSeparatorIfNotPresent(string &$namespaceAttribute)
    {
        if (substr($namespaceAttribute, -1) !== WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            $namespaceAttribute = $namespaceAttribute . WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT;
        }
    }

    /**
     * @param string $path - path or id
     * @param string $drive
     * @param string|null $rev
     * @return WikiPath
     */
    public
    static function createFromPath(string $path, string $drive, string $rev = null): WikiPath
    {
        return new WikiPath($path, $drive, $rev);
    }


    public
    static function getContextPath(): WikiPath
    {
        return ExecutionContext::getActualOrCreateFromEnv()->getContextPath();
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
     * as an {@link WikiPath::getWikiId() id} is a validated absolute path without root character
     */
    public
    static function normalizeWikiPath(string $id)
    {
        return str_replace(WikiPath::NAMESPACE_SEPARATOR_SLASH, WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, $id);
    }

    public
    static function createRootNamespacePathOnMarkupDrive(): WikiPath
    {
        return WikiPath::createMarkupPathFromPath(self::NAMESPACE_SEPARATOR_DOUBLE_POINT);
    }

    /**
     * @param $path
     * @return string with the root path
     */
    public
    static function removeRootSepIfPresent($path): string
    {
        $id = $path;
        if ($id[0] === WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            return substr($id, 1);
        }
        return $id;
    }


    /**
     * The last part of the path
     * @throws ExceptionNotFound
     */
    public
    function getLastName(): string
    {
        /**
         * See also {@link noNSorNS}
         */
        $names = $this->getNames();
        $lastName = $names[sizeOf($names) - 1] ?? null;
        if ($lastName === null) {
            throw new ExceptionNotFound("This path ($this) does not have any last name");
        }
        return $lastName;
    }

    public
    function getNames(): array
    {

        $actualNames = explode(self::NAMESPACE_SEPARATOR_DOUBLE_POINT, $this->absolutePath);

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
    public
    function isPage(): bool
    {

        if (
            $this->drive === self::MARKUP_DRIVE
            &&
            !$this->isGlob()
        ) {
            return true;
        } else {
            return false;
        }

    }


    public
    function isGlob(): bool
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
     * @return string - the wiki id is the absolute path
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

        return $this->absolutePath;

    }


    public
    function getScheme(): string
    {

        return WikiFileSystem::SCHEME;

    }

    /**
     * The wiki revision value
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
        /**
         * Empty because the value may be null or empty string
         */
        if (empty($this->rev)) {
            throw new ExceptionNotFound("The rev was not set");
        }
        return $this->rev;
    }

    /**
     *
     * @throws ExceptionNotFound - if the revision is not set and the path does not exist
     */
    public
    function getRevisionOrDefault()
    {
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
     * Otherwise everywhere in Dokuwiki, they use the {@link WikiPath::getWikiId()} absolute value that does not have any root separator
     * and is absolute (internal index, function, ...)
     *
     */
    public
    function getAbsolutePath(): string
    {

        return $this->absolutePath;

    }

    /**
     * @return array the pages where the wiki file (page or media) is used
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

    public
    function isPublic(): bool
    {
        return $this->getAuthAclValue() >= AUTH_READ;
    }

    /**
     * @return int - An AUTH_ value for this page for the current logged user
     * See the file defines.php
     *
     */
    public
    function getAuthAclValue(): int
    {
        return auth_quickaclcheck($this->getWikiId());
    }


    public
    static function getReservedWords(): array
    {
        if (self::$reservedWords == null) {
            self::$reservedWords = array_merge(Url::RESERVED_WORDS, LocalPath::RESERVED_WINDOWS_CHARACTERS);
        }
        return self::$reservedWords;
    }


    /**
     * The absolute path for a wiki path
     * @return string - the wiki id with a root separator
     */
    function toAbsoluteId(): string
    {
        return self::NAMESPACE_SEPARATOR_DOUBLE_POINT . $this->getWikiId();
    }


    function toAbsolutePath(): Path
    {
        return new WikiPath($this->absolutePath, $this->drive, $this->rev);
    }

    /**
     * The parent path is a directory (namespace)
     * The root path throw an errors
     *
     * @return WikiPath
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
                throw new ExceptionNotFound("The path `{$this}` does not have any parent");
            case 1:
                return new WikiPath(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, $this->drive, $this->rev);
            default:
                $names = array_slice($names, 0, sizeof($names) - 1);
                $path = implode(WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT, $names);
                /**
                 * Because DokuPath does not have the notion of extension
                 * if this is a page, we don't known if this is a directory
                 * or a page. To make the difference, we add a separator at the end
                 */
                $sep = self::NAMESPACE_SEPARATOR_DOUBLE_POINT;
                $path = "$sep$path$sep";
                return new WikiPath($path, $this->drive, $this->rev);
        }

    }

    /**
     * @throws ExceptionNotFound
     */
    function getMime(): Mime
    {
        if ($this->drive === self::MARKUP_DRIVE) {
            return new Mime(Mime::PLAIN_TEXT);
        }
        return FileSystems::getMime($this);

    }


    public
    function getDrive(): string
    {
        return $this->drive;
    }

    public
    function resolve(string $name): WikiPath
    {

        // Directory path have already separator at the end, don't add it
        if ($this->absolutePath[strlen($this->absolutePath) - 1] !== WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT) {
            $path = $this->absolutePath . WikiPath::NAMESPACE_SEPARATOR_DOUBLE_POINT . $name;
        } else {
            $path = $this->absolutePath . $name;
        }
        return new WikiPath($path, $this->getDrive());

    }


    function toUriString(): string
    {
        $driveSep = self::DRIVE_SEPARATOR;
        $absolutePath = self::removeRootSepIfPresent($this->absolutePath);
        $uri = "{$this->getScheme()}://$this->drive$driveSep$absolutePath";
        if (!empty($this->rev)) {
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

    public
    function resolveId($markupId): WikiPath
    {
        if ($this->getDrive() !== self::MARKUP_DRIVE) {
            return $this->resolve($markupId);
        }
        if (!WikiPath::isNamespacePath($this->absolutePath)) {
            try {
                $contextId = $this->getParent()->getWikiId() . self::NAMESPACE_SEPARATOR_DOUBLE_POINT;
            } catch (ExceptionNotFound $e) {
                $contextId = "";
            }
        } else {
            $contextId = $this->getWikiId();
        }
        return WikiPath::createMarkupPathFromId($contextId . $markupId);

    }

    /**
     * @return LocalPath
     * TODO: change it for a constructor on LocalPath
     * @throws ExceptionCast
     */
    public
    function toLocalPath(): LocalPath
    {
        /**
         * File path
         */
        $isNamespacePath = self::isNamespacePath($this->absolutePath);
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
                    $localPath = LocalPath::createFromPathString($conf['mediadir']);
                    break;
                case self::MARKUP_DRIVE:
                    $localPath = LocalPath::createFromPathString($conf['datadir']);
                    break;
                default:
                    $localPath = WikiPath::getDriveRoots()[$this->drive];
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
            case self::MARKUP_DRIVE:
                /**
                 * Adaptation of {@link WikiFN}
                 */
                global $conf;
                try {
                    $extension = $this->getExtension();
                } catch (ExceptionNotFound $e) {
                    LogUtility::internalError("For a markup path file, the extension should have been set. This is not the case for ($this)");
                    $extension = self::MARKUP_DEFAULT_TXT_EXTENSION;
                }
                $idFileSystem = str_replace(':', '/', $this->id);
                if (empty($this->rev)) {
                    $filePathString = Site::getPageDirectory()->resolve(utf8_encodeFN($idFileSystem) . '.' . $extension)->toAbsoluteId();
                } else {
                    $filePathString = Site::getOldDirectory()->resolve(utf8_encodeFN($idFileSystem) . '.' . $this->rev . '.' . $extension)->toAbsoluteId();
                    if ($conf['compression']) {
                        //test for extensions here, we want to read both compressions
                        if (file_exists($filePathString . '.gz')) {
                            $filePathString .= '.gz';
                        } elseif (file_exists($filePathString . '.bz2')) {
                            $filePathString .= '.bz2';
                        } else {
                            // File doesnt exist yet, so we take the configured extension
                            $filePathString .= '.' . $conf['compression'];
                        }
                    }
                }

                break;
            default:
                $baseDirectory = WikiPath::getDriveRoots()[$this->drive];
                if ($baseDirectory === null) {
                    // We don't throw, the file will just not exist
                    // this is metadata
                    throw new ExceptionCast("The drive ($this->drive) is unknown, the local file system path could not be found");
                }
                $filePath = $baseDirectory;
                foreach ($this->getNames() as $name) {
                    $filePath = $filePath->resolve($name);
                }
                $filePathString = $filePath->toAbsoluteId();
                break;
        }
        return LocalPath::createFromPathString($filePathString);

    }

    public function hasRevision(): bool
    {
        try {
            $this->getRevision();
            return true;
        } catch (ExceptionNotFound $e) {
            return false;
        }
    }

}
