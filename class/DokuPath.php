<?php

namespace ComboStrap;

require_once(__DIR__ . '/File.php');

class DokuPath extends File
{
    const MEDIA_TYPE = "media";
    const PAGE_TYPE = "page";
    const UNKNOWN_TYPE = "page";
    private $id;
    private $type;
    /**
     * @var string
     */
    private $finalType;
    /**
     * @var string|null
     */
    private $rev;

    /**
     * DokuPath constructor.
     *
     * protected and not private
     * otherwise the cascading init will not work
     *
     * @param string $id - the id
     * @param string $type - the type (media or page)
     * @param string $rev - the revision (mtime)
     */
    protected function __construct($id, $type, $rev = null)
    {
        $this->id = $id;
        $this->type = $type;

        /**
         * ACL check does not care about the type of id
         * https://www.dokuwiki.org/devel:event:auth_acl_check
         * https://github.com/splitbrain/dokuwiki/issues/3476
         *
         * We check if there is an extension
         * If this is the case, this is a media
         */
        if ($type == self::UNKNOWN_TYPE) {
            $lastPosition = StringUtility::lastIndexOf($this->id, ".");
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
                $path = mediaFN($id, $rev);
            } else {
                $path = mediaFN($id);
            }
        } else {
            if (!empty($rev)) {
                $path = wikiFN($id, $rev);
            } else {
                $path = wikiFN($id);
            }
        }
        parent::__construct($path);
    }


    /**
     *
     * @param $id
     * @return DokuPath
     */
    public static function createPageFromId($id)
    {
        return new DokuPath($id, DokuPath::PAGE_TYPE);
    }

    public static function createMediaLinkFromId($id, $rev= '')
    {
        return new DokuPath($id, DokuPath::MEDIA_TYPE, $rev);
    }

    public static function createUnknownFromId($id)
    {
        return new DokuPath($id, DokuPath::UNKNOWN_TYPE);
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
        return StringUtility::endWiths($this->id, ":*");
    }

    public function __toString()
    {
        return $this->id;
    }

    /**
     * @return string - the id (fully qualified and normalized)
     */
    public function getId()
    {
        return cleanID($this->id);
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
    public function getRevision(){
        return $this->rev;
    }


}
