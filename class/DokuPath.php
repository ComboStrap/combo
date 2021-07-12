<?php

namespace ComboStrap;

require_once(__DIR__ . '/File.php');

class DokuPath extends File
{
    const MEDIA_TYPE = "media";
    const PAGE_TYPE = "page";
    const UNKNOWN_TYPE = "unknown";
    const SEPARATOR = ":";

    // https://www.dokuwiki.org/config:useslash
    const SEPARATOR_SLASH = "/";

    const SEPARATORS = [self::SEPARATOR, self::SEPARATOR_SLASH];
    const LOCAL_SCHEME = 'local'; // knwon also as internal media
    const INTERWIKI_SCHEME = 'interwiki';
    const INTERNET_SCHEME = "internet";
    const PATH_ATTRIBUTE = "path";

    /**
     * @var string the path id passed to function (cleaned)
     */
    private $path;

    /**
     * @var string the absolute id with the root separator
     * See {@link $absolutePathWithoutRootSeparator} for the absolute id without root separator for the index
     */
    private $absoluteIdWithSeparator;

    /**
     * @var string
     */
    private $finalType;
    /**
     * @var string|null
     */
    private $rev;

    /**
     * @var string a value with an absolute id without the root
     * used in the index (ie the id)
     */
    private $absolutePathWithoutRootSeparator;

    /**
     * @var string the path scheme one constant that starts with SCHEME
     * ie
     * {@link DokuPath::LOCAL_SCHEME},
     * {@link DokuPath::INTERNET_SCHEME},
     * {@link DokuPath::INTERWIKI_SCHEME}
     */
    private $scheme;


    /**
     * DokuPath constructor.
     *
     * protected and not private
     * otherwise the cascading init will not work
     *
     * @param string $path - the logical dokuwiki path (may be relative or not)
     * @param string $type - the type (media, page)
     * @param string $rev - the revision (mtime)
     */
    protected function __construct($path, $type, $rev = null)
    {

        if (empty($path)) {
            LogUtility::msg("A null path was given", LogUtility::LVL_MSG_WARNING);
        }
        $this->path = $path;


        // Check whether this is a local or remote image or interwiki
        if (media_isexternal($path)) {

            $this->scheme = self::INTERNET_SCHEME;

        } else if (link_isinterwiki($path)) {

            $this->scheme = self::INTERWIKI_SCHEME;

        } else {

            $this->scheme = self::LOCAL_SCHEME;

            // https://www.dokuwiki.org/config:useslash
            global $conf;
            if ($conf['useslash']) {
                $path = str_replace(self::SEPARATOR_SLASH, self::SEPARATOR, $path);
            }
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
            $lastPosition = StringUtility::lastIndexOf($path, ".");
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
        $filePath = $this->path;
        if ($this->scheme == self::LOCAL_SCHEME) {
            /**
             * Absolute id cleaned for the index
             * See the $page argument of {@link resolve_pageid}
             * Resolution clean the id {@link cleanID()}
             */
            global $ID;
            $this->absolutePathWithoutRootSeparator = $this->path;

            $isNamespace = false;
            if (mb_substr($this->path, -1) == self::SEPARATOR) {
                $isNamespace = true;
            }

            if (!$isNamespace) {
                /**
                 * File (Page or media)
                 */
                if ($this->finalType == self::MEDIA_TYPE) {
                    resolve_mediaid(getNS($ID), $this->absolutePathWithoutRootSeparator, $exists);
                } else {
                    resolve_pageid(getNS($ID), $this->absolutePathWithoutRootSeparator, $exists);
                }
                $this->absoluteIdWithSeparator = self::SEPARATOR . $this->absolutePathWithoutRootSeparator;


                if ($type == self::MEDIA_TYPE) {
                    if (!empty($rev)) {
                        $filePath = mediaFN($this->absolutePathWithoutRootSeparator, $rev);
                    } else {
                        $filePath = mediaFN($this->absolutePathWithoutRootSeparator);
                    }
                } else {
                    if (!empty($rev)) {
                        $filePath = wikiFN($this->absolutePathWithoutRootSeparator, $rev);
                    } else {
                        $filePath = wikiFN($this->absolutePathWithoutRootSeparator);
                    }
                }
            } else {
                /**
                 * Namespace
                 */
                $this->absolutePathWithoutRootSeparator = resolve_id(getNS($ID), $this->absolutePathWithoutRootSeparator, true);

                global $conf;
                if ($type == self::MEDIA_TYPE) {
                    $filePath = $conf['mediadir'] . '/' . utf8_encodeFN($this->absolutePathWithoutRootSeparator);
                } else {
                    $filePath = $conf['datadir'] . '/' . utf8_encodeFN($this->absolutePathWithoutRootSeparator);
                }
            }
        }
        parent::__construct($filePath);
    }


    /**
     *
     * @param $pathId
     * @return DokuPath
     */
    public static function createPagePathFromPath($pathId)
    {
        return new DokuPath($pathId, DokuPath::PAGE_TYPE);
    }

    public static function createMediaPathFromPath($path, $rev = '')
    {
        return new DokuPath($path, DokuPath::MEDIA_TYPE, $rev);
    }

    public static function createUnknownFromId($id)
    {
        return new DokuPath(DokuPath::SEPARATOR . $id, DokuPath::UNKNOWN_TYPE);
    }

    /**
     * @param $url - a URL path http://whatever/hello/my/lord (The canonical)
     * @return string - a dokuwiki Id hello:my:lord
     */
    public static function createFromUrl($url)
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
        return self::createUnknownFromId($id);
    }

    /**
     * Static don't ask why
     * @param $pathId
     * @return false|string
     */
    public static function getLastPart($pathId)
    {
        $endSeparatorLocation = StringUtility::lastIndexOf($pathId, DokuPath::SEPARATOR);
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
        return DokuPath::SEPARATOR . $id;
    }

    public static function AbsolutePathToId($absolutePath)
    {
        return substr($absolutePath, 1);
    }


    public function getName()
    {
        /**
         * See also {@link noNSorNS}
         */
        $names = $this->getNames();
        return $names[sizeOf($names) - 1];
    }

    public function getNames()
    {
        return preg_split("/" . self::SEPARATOR . "/", $this->getId());
    }

    /**
     * @return bool true if this id represents a page
     */
    public function isPage()
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


    public function isGlob()
    {
        /**
         * {@link search_universal} triggers ACL check
         * with id of the form :path:*
         * (for directory ?)
         */
        return StringUtility::endWiths($this->getId(), ":*");
    }

    public function __toString()
    {
        return $this->getId();
    }

    /**
     * @return string - the id of dokuwiki is the absolute path
     * without the root separator (ie normalized)
     *
     * The index stores and needs this value
     * And most of the function that are not links related
     * use this format
     */
    public function getId()
    {

        if ($this->getScheme() == self::LOCAL_SCHEME) {
            return $this->absolutePathWithoutRootSeparator;
        } else {
            // the url (it's stored as id in the metadata)
            return $this->path;
        }

    }

    public function getPath()
    {

        return $this->path;

    }

    public function getScheme()
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
    public function getRevision()
    {
        return $this->rev;
    }


    /**
     * @return string
     *
     * This is the absolute path WITH the root separator
     *
     * This is generally NOT what you need.
     * unless you use this value on {@link MediaLink} and {@link DokuPath} because in this functions it may be relative or not
     *
     * Otherwise everywhere in Dokuwiki, use the {@link DokuPath::getId()} absolute value that does not have any root separator
     * and is absolute (index, ...)
     *
     */
    public function getAbsolutePath()
    {
        if ($this->getScheme() == self::LOCAL_SCHEME) {
            return $this->absoluteIdWithSeparator;
        } else {
            // otherwise (url) return the path id
            return $this->path;
        }

    }

    /**
     * @return array the pages where the dokuwiki file (page or media) is used
     *   * backlinks for page
     *   * page with media for media
     */
    public function getRelatedPages()
    {
        $absoluteId = $this->getId();
        if ($this->finalType == self::MEDIA_TYPE) {
            return idx_get_indexer()->lookupKey('relation_media', $absoluteId);
        } else {
            return idx_get_indexer()->lookupKey('relation_references', $absoluteId);
        }
    }

    public function isPathIdAbsolute()
    {
        return strpos($this->path, self::SEPARATOR) === 0;
    }

    /**
     * Return the path relative to the base directory
     * (ie $conf[basedir])
     * @return string
     */
    public function toRelativeFileSystemPath()
    {
        $relativeSystemPath = ".";
        if (!empty($this->getId())) {
            $relativeSystemPath .= "/" . utf8_encodeFN(str_replace(':', '/', $this->getId()));
        }
        return $relativeSystemPath;

    }

}
