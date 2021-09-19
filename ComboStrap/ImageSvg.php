<?php


namespace ComboStrap;

/**
 * Class ImageSvg
 * @package ComboStrap
 * A svg image
 */
class ImageSvg extends Image
{

            const MIME = "image/svg+xml";
    const EXTENSION = "svg";

    public function __construct($absolutePath, $rev = null)
            {
                parent::__construct($absolutePath, DokuPath::MEDIA_TYPE, $rev);
            }


    /**
     * @var SvgDocument
     */
    private $svgDocument;

    public function getWidth()
    {
        return $this->getSvgDocument()->getMediaWidth();
    }

    public function getHeight()
    {
        return $this->getSvgDocument()->getMediaHeight();
    }

    public function getSvgDocument(): SvgDocument
    {
        if ($this->svgDocument == null) {
            $this->svgDocument = SvgDocument::createFromPath($this);
        }
        return $this->svgDocument;
    }

}
