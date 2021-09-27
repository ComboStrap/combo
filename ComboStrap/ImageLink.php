<?php


namespace ComboStrap;


/**
 * Class ImageLink
 * @package ComboStrap
 *
 * A media of image type
 */
abstract class ImageLink extends MediaLink
{


    function getDefaultImage(): Image
    {
        if (!($this->getMedia() instanceof Image)) {
            LogUtility::msg("The media ($this) is not an image", LogUtility::LVL_MSG_ERROR);
        }
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getMedia();
    }

    /**
     * @return string the wiki syntax
     */
    public function getMarkupSyntax(): string
    {
        $descriptionPart = "";
        if (!empty($this->getDefaultImage()->getAltNotEmpty())) {
            $descriptionPart = "|" . $this->getDefaultImage()->getAltNotEmpty();
        }
        return '{{' . $this->getMedia()->getAbsolutePath() . $descriptionPart . '}}';
    }

}
