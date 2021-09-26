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
        return $this->getMedia();
    }

    /**
     * @return string the wiki syntax
     */
    public  function getMarkupSyntax(): string
    {
        $descriptionPart = "";
        if (!empty($this->getDefaultImage()->getAlt())) {
            $descriptionPart = "|" . $this->getDefaultImage()->getAlt();
        }
        return '{{' . $this->getMedia()->getAbsolutePath() . $descriptionPart . '}}';
    }

}
