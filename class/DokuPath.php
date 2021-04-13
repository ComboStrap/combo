<?php


namespace ComboStrap;


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
     * DokuPath constructor.
     */
    public function __construct($id,$type)
    {
        $this->id = $id;
        $this->type = $type;

        /**
         * ACL check does not have the type
         * of id
         * https://www.dokuwiki.org/devel:event:auth_acl_check
         * We check if there is an extension
         * If this is the case, this is a media
         */
        if ($type==self::UNKNOWN_TYPE) {
            $lastPosition = StringUtility::lastIndexOf($this->id, ".");
            if ($lastPosition === FALSE) {
                $type=self::PAGE_TYPE;
            } else {
                $type=self::MEDIA_TYPE;
            }
        }
        $this->finalType = $type;


        if ($type==self::MEDIA_TYPE){
            $path = mediaFN($id);
        } else {
            $path = wikiFN($id);
        }
        parent::__construct($path);
    }


    /**
     *
     * @param $id
     * @param string $type
     * @return DokuPath
     */
    public static function createFromId($id, $type = self::UNKNOWN_TYPE)
    {
        return new DokuPath($id, $type);
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




}
