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
        return $this->getDokuPath();
    }

}
