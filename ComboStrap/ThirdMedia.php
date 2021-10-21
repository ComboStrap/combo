<?php


namespace ComboStrap;


class ThirdMedia extends Media
{

    public function getUrl()
    {

        LogUtility::msg("The media with the mime (" . $this->getMime() . ") is not yet implemented", LogUtility::LVL_MSG_ERROR);
        return "https://combostrap.com/media/not/yet/implemented";

    }
}