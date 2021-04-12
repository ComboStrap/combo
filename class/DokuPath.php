<?php


namespace ComboStrap;


class DokuPath
{
    private $id;

    /**
     * DokuPath constructor.
     */
    public function __construct($id)
    {
        $this->id = $id;
    }


    /**
     *
     * @param $id
     * @return DokuPath
     */
    public static function createFromId($id)
    {
        return new DokuPath($id);
    }

    public function isPage()
    {
        /**
         * Page id does not have extension
         * While media does
         **/
        if (
            $this->getExtension() === null
            &&
            !$this->isDirectory()
        ) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Page id does not have extension
     * While media does
     * For an id,  without extension
     */
    private function getExtension()
    {
        $lastPosition = StringUtility::lastIndexOf($this->id,".");
        if ($lastPosition === FALSE) {
            return null;
        } else {
            return substr($this->id, $lastPosition + 1);
        }
    }

    public function isDirectory()
    {
        /**
         * {@link search_universal} triggers ACL check
         * with id of the form :path:*
         * for directory
         */
        return StringUtility::endWiths($this->id, ":*");
    }

}
