<?php

namespace ComboStrap;

require_once(__DIR__ . '/PluginUtility.php');

/**
 * Class DokuPath
 * @package ComboStrap
 * A dokuwiki path
 *
 */
class DokuPath extends PathAbs
{
    const MEDIA_TYPE = "media";
    const PAGE_TYPE = "page";
    const UNKNOWN_TYPE = "unknown";
    const PATH_SEPARATOR = ":";

    // https://www.dokuwiki.org/config:useslash
    const SEPARATOR_SLASH = "/";

    const SEPARATORS = [self::PATH_SEPARATOR, self::SEPARATOR_SLASH];

    /**
     * For whatever reason, dokuwiki uses also on windows
     * the linux separator
     */
    public const DIRECTORY_SEPARATOR = "/";
    public const SLUG_SEPARATOR = "-";

    const RESOURCE_TYPE = "resource";
    /**
     * Dokuwiki know as file system starts at page and media
     * This parameters permits to add another one
     * that starts at the resource directory
     */
    public const WIKI_FS_TYPE = "wiki-fs-type";

    /**
     * @var string[]
     */
    private static $reservedWords;

    /**
     * @var string the path id passed to function (cleaned)
     */
    private $id;

    /**
     * @var string the absolute id with the root separator
     * See {@link $id} for the absolute id without root separator for the index
     */
    private $absolutePath;

    /**
     * @var string
     */
    private $finalType;
    /**
     * @var string|null - ie mtime
     */
    private $rev;

    /**
     * @var string a value with an absolute id without the root
     * used in the index (ie the id)
     */
    private $qualifiedId;

    /**
     * @var string the path scheme one constant that starts with SCHEME
     * ie
     * {@link DokuFs::SCHEME},
     * {@link InternetPath::scheme},
     * {@link InterWikiPath::scheme}
     */
    private $scheme;
    private $filePath;


    /**
     * DokuPath constructor.
     *
     * A path for the Dokuwiki File System
     *
     * @param string $absolutePath - the dokuwiki absolute path (may not be relative but may be a namespace)
     * @param string $type - the type (media, page)
     * @param string|null $rev - the revision (mtime)
     *
     * Thee path should be a qualified/absolute path because in Dokuwiki, a link to a {@link Page}
     * that ends with the {@link DokuPath::PATH_SEPARATOR} points to a start page
     * and not to a namespace. The qualification occurs in the transformation
     * from ref to page.
     *   For a page: in {@link LinkUtility::getInternalPage()}
     *   For a media: in the {@link MediaLink::createMediaLinkFromId()}
     * Because this class is mostly the file representation, it should be able to
     * represents also a namespace
     */
    protected function __construct(string $absolutePath, string $type, string $rev = null)
    {
        DokuPath::addRootSeparatorIfNotPresent($absolutePath);
        if (empty($absolutePath)) {
            LogUtility::msg("A null path was given", LogUtility::LVL_MSG_WARNING);
        }
        $this->absolutePath = $absolutePath;


        // Check whether this is a local or remote image or interwiki
        if (media_isexternal($absolutePath)) {

            $this->scheme = InternetPath::scheme;

        } else if (link_isinterwiki($absolutePath)) {

            $this->scheme = InterWikiPath::scheme;

        } else {

            if (substr($absolutePath, 0, 1) !== DokuPath::PATH_SEPARATOR) {
                if (PluginUtility::isDevOrTest()) {
                    // Feel too much the log, test are not seeing anything, may be minimap ?
                    LogUtility::msg("The path given ($absolutePath) is not qualified", LogUtility::LVL_MSG_ERROR);
                }
                $this->absolutePath = ":" . $absolutePath;
            }
            if (substr($absolutePath, 1, 1) === DokuPath::PATH_SEPARATOR) {
                /**
                 * path given is `::path`
                 */
                if (PluginUtility::isDevOrTest()) {
                    LogUtility::msg("The path given ($absolutePath) has too much separator", LogUtility::LVL_MSG_ERROR);
                }
            }
            $this->scheme = DokuFs::SCHEME;

        }


        /**
         * ACL check does not care about the type of id
         * https://www.dokuwiki.org/devel:event:auth_acl_check
         * https://github.com/splitbrain/dokuwiki/issues/3476
         *
         * We check if there is an extension
         * If this is the case, this is a media
         */
        if ($type == self::UNKNOWN_TYPE) {
            $lastPosition = StringUtility::lastIndexOf($absolutePath, ".");
            if ($lastPosition === FALSE) {
                $type = self::PAGE_TYPE;
            } else {
                $type = self::MEDIA_TYPE;
            }
        }
        $this->finalType = $type;
        $this->rev = $rev;

        /**
         * File path
         */
        $filePath = $this->absolutePath;
        if ($this->scheme == DokuFs::SCHEME) {

            $this->id = DokuPath::toDokuwikiId($this->absolutePath);
            $isNamespacePath = false;
            if (\mb_substr($this->absolutePath, -1) == self::PATH_SEPARATOR) {
                $isNamespacePath = true;
            }

            global $ID;

            if (!$isNamespacePath) {

                switch ($type) {

                    case self::MEDIA_TYPE:
                        if (!empty($rev)) {
                            $filePath = mediaFN($this->id, $rev);
                        } else {
                            $filePath = mediaFN($this->id);
                        }
                        break;
                    case self::PAGE_TYPE:
                        if (!empty($rev)) {
                            $filePath = wikiFN($this->id, $rev);
                        } else {
                            $filePath = wikiFN($this->id);
                        }
                        break;
                    case self::RESOURCE_TYPE:
                        $relativeFsPath = DokuPath::toFileSystemSeparator($this->id);
                        $filePath = Resources::getAbsoluteResourcesDirectory() . DIRECTORY_SEPARATOR . $relativeFsPath;
                        break;
                    default:
                        LogUtility::msg("Wiki Path Type ($type) is unknown, the local file system path could not be found", LogUtility::LVL_MSG_ERROR);

                }
            } else {
                /**
                 * Namespace
                 * (Fucked up is fucked up)
                 * We qualify for the namespace here
                 * because there is no link or media for a namespace
                 */
                $this->id = resolve_id(getNS($ID), $this->id, true);
                global $conf;
                if ($type == self::MEDIA_TYPE) {
                    $filePath = $conf['mediadir'] . '/' . utf8_encodeFN($this->id);
                } else {
                    $filePath = $conf['datadir'] . '/' . utf8_encodeFN($this->id);
                }
            }
        }
        $this->filePath = $filePath;
    }


    /**
     *
     * @param $absolutePath
     * @return DokuPath
     */
    public static function createPagePathFromPath($absolutePath): DokuPath
    {
        return new DokuPath($absolutePath, DokuPath::PAGE_TYPE);
    }

    public static function createMediaPathFromAbsolutePath($absolutePath, $rev = ''): DokuPath
    {
        return new DokuPath($absolutePath, DokuPath::MEDIA_TYPE, $rev);
    }

    public static function createUnknownFromIdOrPath($id): DokuPath
    {
        DokuPath::addRootSeparatorIfNotPresent($id);
        return new DokuPath($id, DokuPath::UNKNOWN_TYPE);
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
        $endSeparatorLocation = StringUtility::lastIndexOf($pathId, DokuPath::PATH_SEPARATOR);
        if ($endSeparatorLocation === false) {
            $endSeparatorLocation = StringUtility::lastIndexOf($pathId, DokuPath::SEPARATOR_SLASH);
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
        return DokuPath::PATH_SEPARATOR . $id;
    }

    public
    static function toDokuwikiId($absolutePath)
    {
        // Root ?
        if ($absolutePath == DokuPath::PATH_SEPARATOR) {
            return "";
        }
        if ($absolutePath[0] === DokuPath::PATH_SEPARATOR) {
            return substr($absolutePath, 1);
        }
        return $absolutePath;

    }

    public static function createMediaPathFromId($id, $rev = ''): DokuPath
    {
        DokuPath::addRootSeparatorIfNotPresent($id);
        return self::createMediaPathFromAbsolutePath($id, $rev);
    }


    public static function createPagePathFromId($id): DokuPath
    {
        return new DokuPath(DokuPath::PATH_SEPARATOR . $id, self::PAGE_TYPE);
    }

    /**
     * If the path does not have a root separator,
     * it's added (ie to transform an id to a path)
     * @param string $path
     */
    public static function addRootSeparatorIfNotPresent(string &$path)
    {
        if (substr($path, 0, 1) !== ":") {
            $path = DokuPath::PATH_SEPARATOR . $path;
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

    public static function toFileSystemSeparator($dokuPath)
    {
        return str_replace(":", self::DIRECTORY_SEPARATOR, $dokuPath);
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

    public static function createResource($dokuwikiId): DokuPath
    {
        return new DokuPath($dokuwikiId, self::RESOURCE_TYPE);
    }

    public static function createDokuPath($mediaId, $type, $rev = ''): DokuPath
    {
        return new DokuPath($mediaId, $type, $rev);
    }


    /**
     * The last part of the path
     */
    public
    function getLastName()
    {
        /**
         * See also {@link noNSorNS}
         */
        $names = $this->getNames();
        return $names[sizeOf($names) - 1];
    }

    /**
     * @return string
     */
    public function getLastNameWithoutExtension(): string
    {
        /**
         * A page doku path has no extension for now
         */
        if ($this->finalType === self::PAGE_TYPE) {
            return $this->getLastName();
        }
        return parent::getLastNameWithoutExtension();
    }


    public
    function getNames()
    {

        $names = explode(self::PATH_SEPARATOR, $this->getDokuwikiId());

        if ($names[0] === "") {
            /**
             * Case of only one string without path separator
             * the first element returned is an empty string
             */
            $names = array_splice($names, 1);
        }
        return $names;
    }

    /**
     * @return bool true if this id represents a page
     */
    public function isPage(): bool
    {

        if (
            $this->finalType === self::PAGE_TYPE
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
        return StringUtility::endWiths($this->getDokuwikiId(), ":*");
    }

    public
    function __toString()
    {
        return $this->getDokuwikiId();
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
     * /**
     * The absolute path without root separator
     * Heavily used inside Dokuwiki
     */
    public
    function getDokuwikiId(): string
    {

        if ($this->getScheme() == DokuFs::SCHEME) {
            return $this->id;
        } else {
            // the url (it's stored as id in the metadata)
            return $this->getPath();
        }

    }

    public
    function getPath(): string
    {

        return $this->absolutePath;

    }

    public
    function getScheme()
    {

        return $this->scheme;

    }

    /**
     * The dokuwiki revision value
     * as seen in the {@link basicinfo()} function
     * is the {@link File::getModifiedTime()} of the file
     *
     * Let op passing a revision to Dokuwiki will
     * make ti search to the history
     * The actual file will then not be found
     *
     * @return string|null
     */
    public
    function getRevision()
    {
        return $this->rev;
    }


    /**
     * @return string
     *
     * This is the local absolute path WITH the root separator.
     * It's used in ref present in {@link LinkUtility link} or {@link MediaLink}
     * when creating test, otherwise the ref is considered as relative
     *
     *
     * Otherwise everywhere in Dokuwiki, they use the {@link DokuPath::getDokuwikiId()} absolute value that does not have any root separator
     * and is absolute (internal index, function, ...)
     *
     */
    public
    function getAbsolutePath(): string
    {

        return $this->absolutePath;

    }

    /**
     * @return array the pages where the dokuwiki file (page or media) is used
     *   * backlinks for page
     *   * page with media for media
     */
    public
    function getReferencedBy(): array
    {
        $absoluteId = $this->getDokuwikiId();
        if ($this->finalType == self::MEDIA_TYPE) {
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
    function toRelativeFileSystemPath()
    {
        $relativeSystemPath = ".";
        if (!empty($this->getDokuwikiId())) {
            $relativeSystemPath .= "/" . utf8_encodeFN(str_replace(':', '/', $this->getDokuwikiId()));
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
        return auth_quickaclcheck($this->getDokuwikiId());
    }


    public static function getReservedWords(): array
    {
        if (self::$reservedWords == null) {
            $reservedWords = array_merge(Url::RESERVED_WORDS, LocalPath::RESERVED_WINDOWS_CHARACTERS);
            self::$reservedWords = array_filter($reservedWords, function ($e) {
                return $e != DokuPath::PATH_SEPARATOR;
            });
        }
        return self::$reservedWords;
    }

    /**
     * @return string - a label from a path (used in link when their is no label available)
     * The path is separated in words and every word gets an uppercase letter
     */
    public function toLabel(): string
    {
        $words = preg_split("/\s/", preg_replace("/-|_|:/", " ", $this->getPath()));
        $wordsUc = [];
        foreach ($words as $word) {
            $wordsUc[] = ucfirst($word);
        }
        return implode(" ", $wordsUc);
    }

    public function toLocalPath(): LocalPath
    {
        return LocalPath::create($this->filePath);
    }

    function toString(): string
    {
        return $this->absolutePath;
    }

    function toUriString(): string
    {
        $string = "{$this->scheme}://$this->finalType/$this->id";
        if ($this->rev !== null) {
            return "$string?rev={$this->rev}";
        }
        return $string;
    }

    function toAbsolutePath(): Path
    {
        return new DokuPath($this->absolutePath, $this->finalType, $this->rev);
    }

    /**
     * The parent path is a directory (namespace)
     * The parent of page in the root does return the root namespace.
     *
     * @return DokuPath|null
     */
    function getParent(): ?Path
    {

        /**
         * Same as {@link getNS()}
         */
        $names = $this->getNames();
        switch (sizeof($names)) {
            case 0:
                return null;
            case 1:
                return new DokuPath(DokuPath::PATH_SEPARATOR, $this->finalType, $this->rev);
            default:
                $names = array_slice($names, 0, sizeof($names) - 1);
                $path = implode(DokuPath::PATH_SEPARATOR, $names);
                return new DokuPath($path, $this->finalType, $this->rev);
        }

    }

    function getMime(): ?Mime
    {
        if ($this->finalType === self::PAGE_TYPE) {
            return new Mime(Mime::PLAIN_TEXT);
        }
        return parent::getMime();

    }

    public function getType(): string
    {
        return $this->finalType;
    }
}
