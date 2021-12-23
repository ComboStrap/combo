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


    /**
     * @return Image
     */
    function getDefaultImage(): ?Image
    {
        if (!($this->getMedia() instanceof Image)) {
            LogUtility::msg("The media ($this) is not an image", LogUtility::LVL_MSG_ERROR);
        }
        /**
         *
         */
        $media = $this->getMedia();
        if($media instanceof Image){
            return $media;
        } else {
            return null;
        }
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
        return '{{' . $this->getMedia()->getPath()->getAbsolutePath() . $descriptionPart . '}}';
    }

}
