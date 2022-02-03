<?php


namespace ComboStrap;

/**
 * Class ThirdMediaLink
 * @package ComboStrap
 * Not yet implemented but used to
 * returns a media link object and not null
 * otherwise, we get an error
 */
class ThirdMediaLink extends MediaLink
{

    public function renderMediaTag(): string
    {
        $msg = "The media with the mime (" . $this->getMedia()->getPath()->getMime() . ") is not yet implemented";
        LogUtility::msg($msg, LogUtility::LVL_MSG_ERROR);
        return $msg;
    }

    public function getUrl(): string{
        return "";
    }


}
