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

        if (!($this->getPath() instanceof ImageFetch)) {
            LogUtility::msg("The media ($this) is not an image", LogUtility::LVL_MSG_ERROR);
        }
        /**
         *
         */
        $media = $this->getPath();
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
        if (!empty($this->getAltNotEmpty())) {
            $descriptionPart = "|" . $this->getAltNotEmpty();
        }
        return '{{' . $this->getPath()->getAbsolutePath() . $descriptionPart . '}}';
    }

    /**
     * This is mandatory for HTML
     * The alternate text (the title in Dokuwiki media term)
     * @return null
     *
     * TODO: Try to extract it from the metadata file ?
     *
     * An img element must have an alt attribute, except under certain conditions.
     * For details, consult guidance on providing text alternatives for images.
     * https://www.w3.org/WAI/tutorials/images/
     */
    public function getAltNotEmpty()
    {
        $title = $this->getTitle();
        if (!empty($title)) {
            return $title;
        }
        $generatedAlt = str_replace("-", " ", $this->getPath()->getLastNameWithoutExtension());
        return str_replace($generatedAlt, "_", " ");
    }

}
