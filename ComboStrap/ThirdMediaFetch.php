<?php


namespace ComboStrap;


class ThirdMediaFetch extends FetchAbstract
{

    public function getUrl(): string
    {

        LogUtility::msg("The media with the mime (" . $this->getPath()->getMime() . ") is not yet implemented", LogUtility::LVL_MSG_ERROR);
        return "https://combostrap.com/media/not/yet/implemented";

    }
}
