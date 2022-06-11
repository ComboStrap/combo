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
     * @return ImageFetch
     */
    function getDefaultImageFetch(): ?ImageFetch
    {
        if (!($this->getMediaFetch() instanceof ImageFetch)) {
            LogUtility::msg("The media ($this) is not an image", LogUtility::LVL_MSG_ERROR);
        }
        /**
         *
         */
        $media = $this->getMediaFetch();
        if($media instanceof ImageFetch){
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
        if (!empty($this->getDefaultImageFetch()->getAltNotEmpty())) {
            $descriptionPart = "|" . $this->getDefaultImageFetch()->getAltNotEmpty();
        }
        return '{{' . $this->getMediaFetch()->getPath()->getAbsolutePath() . $descriptionPart . '}}';
    }

}
