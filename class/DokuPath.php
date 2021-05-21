<?php

namespace ComboStrap;

require_once(__DIR__ . '/File.php');

class DokuPath extends File
{
    const MEDIA_TYPE = "media";
    const PAGE_TYPE = "page";
    const UNKNOWN_TYPE = "unknown";
    const SEPARATOR = ":";

    /**
     * @var string the path id passed to function (cleaned)
     */
    private $pathId;

    /**
     * @var string the absolute id with the root separator
     * See {@link $absoluteIdWithoutSeparator} for the absolute id without root separator for the index
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
     * used in the index
     */
    private $absoluteIdWithoutSeparator;
    /**
     * @var bool true if the id is an absolute one
     */
    private $isAbsoluteId;

    /**
     * DokuPath constructor.
     *
     * protected and not private
     * otherwise the cascading init will not work
     *
     * @param string $pathId - the id
     * @param string $type - the type (media or page)
     * @param string $rev - the revision (mtime)
     */
    protected function __construct($pathId, $type, $rev = null)
    {

        if (empty($pathId)) {
            LogUtility::msg("A null path id was given", LogUtility::LVL_MSG_WARNING);
        }

        // https://www.dokuwiki.org/config:useslash
        global $conf;
        if ($conf['useslash']) {
            $pathId = str_replace("/", ":", $pathId);
        }

        /**
         * characters are not all authorized, all lowercase
         * such as `_` at the end
         */
        $cleanedId = cleanID($pathId);

        /**
         * The id have no root character due to {@link cleanID()}
         * function, we correct that (and therefore in the index also)
         */
        $this->isAbsoluteId = (strpos($pathId, self::SEPARATOR) === 0);
        if ($this->isAbsoluteId) {
            $this->pathId = self::SEPARATOR . $cleanedId;
        } else {
            $this->pathId = $cleanedId;
        }

        /**
         * Absolute id cleaned for the index
         * See the $page argument of {@link resolve_pageid}
         */
        global $ID;
        $this->absoluteIdWithoutSeparator = $this->pathId;
        if ($this->finalType == self::MEDIA_TYPE) {
            resolve_mediaid(getNS($ID), $this->absoluteIdWithoutSeparator, $exists);
        } else {
            resolve_pageid(getNS($ID), $this->absoluteIdWithoutSeparator, $exists);
        }
        $this->absoluteIdWithSeparator = self::SEPARATOR . $this->absoluteIdWithoutSeparator;


        /**
         * ACL check does not care about the type of id
         * https://www.dokuwiki.org/devel:event:auth_acl_check
         * https://github.com/splitbrain/dokuwiki/issues/3476
         *
         * We check if there is an extension
         * If this is the case, this is a media
         */
        if ($type == self::UNKNOWN_TYPE) {
            $lastPosition = StringUtility::lastIndexOf($this->getId(), ".");
            if ($lastPosition === FALSE) {
                $type = self::PAGE_TYPE;
            } else {
                $type = self::MEDIA_TYPE;
            }
        }
        $this->finalType = $type;
        $this->rev = $rev;


        if ($type == self::MEDIA_TYPE) {
            if (!empty($rev)) {
                $path = mediaFN($this->absoluteIdWithoutSeparator, $rev);
            } else {
                $path = mediaFN($this->absoluteIdWithoutSeparator);
            }
        } else {
            if (!empty($rev)) {
                $path = wikiFN($this->absoluteIdWithoutSeparator, $rev);
            } else {
                $path = wikiFN($this->absoluteIdWithoutSeparator);
            }
        }
        parent::__construct($path);
    }


    /**
     *
     * @param $pathId
     * @return DokuPath
     */
    public static function createPageFromPathId($pathId)
    {
        return new DokuPath($pathId, DokuPath::PAGE_TYPE);
    }

    public static function createMediaPathFromId($id, $rev = '')
    {
        return new DokuPath($id, DokuPath::MEDIA_TYPE, $rev);
    }

    public static function createUnknownFromId($id)
    {
        return new DokuPath($id, DokuPath::UNKNOWN_TYPE);
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

        return $this->absoluteIdWithoutSeparator;

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
     * @deprecated
     *
     * This is not really deprecated but there is a lot of chance that if you use this function
     * not in a test, you are wrong
     *
     * For Dokuwiki, the absolute id does not have any root separator, use {@link DokuPath::getId()}
     */
    public function getAbsoluteIdWithRoot()
    {

        return $this->absoluteIdWithSeparator;

    }

    /**
     * @return array the pages where the media is used
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

    public function isAbsolute()
    {
        return $this->isAbsoluteId;
    }

}
