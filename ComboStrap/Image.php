<?php


namespace ComboStrap;

/**
 * Class Image
 * @package ComboStrap
 * An image
 */
abstract class Image extends DokuPath
{


    /**
     * For a raster image, the internal width
     * for a svg, the defined viewBox
     *
     * This is needed to calculate the {@link MediaLink::getTargetRatio() target ratio}
     * and pass them to the img tag to avoid layout shift
     *
     * @return mixed
     */
    public abstract function getWidth();

    /**
     * For a raster image, the internal height
     * for a svg, the defined `viewBox` value
     *
     * This is needed to calculate the {@link MediaLink::getTargetRatio() target ratio}
     * and pass them to the img tag to avoid layout shift
     *
     * @return mixed
     */
    public abstract function getHeight();

}
