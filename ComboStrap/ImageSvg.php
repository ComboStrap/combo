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
    const CANONICAL = "svg";

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

    /**
     * @param string $ampersand $absolute - the & separator (should be encoded for HTML but not for CSS)
     * @return string|null
     *
     * At contrary to {@link RasterImageLink::getUrl()} this function does not need any width parameter
     */
    public function getUrl($ampersand = DokuwikiUrl::URL_ENCODED_AND, $tagAttributes = null): ?string
    {

        if ($tagAttributes === null) {
            $tagAttributes = TagAttributes::createEmpty(self::CANONICAL);
        }

        if ($this->exists()) {

            /**
             * We remove align and linking because,
             * they should apply only to the img tag
             */


            /**
             *
             * Create the array $att that will cary the query
             * parameter for the URL
             */
            $att = array();
            $componentAttributes = $tagAttributes->getComponentAttributes();
            foreach ($componentAttributes as $name => $value) {

                if (!in_array(strtolower($name), MediaLink::NON_URL_ATTRIBUTES)) {
                    $newName = $name;

                    /**
                     * Width and Height
                     * permits to create SVG of the asked size
                     *
                     * This is a little bit redundant with the
                     * {@link Dimension::processWidthAndHeight()}
                     * `max-width and width` styling property
                     * but you may use them outside of HTML.
                     */
                    switch ($name) {
                        case Dimension::WIDTH_KEY:
                            $newName = "w";
                            /**
                             * We don't remove width because,
                             * the sizing should apply to img
                             */
                            break;
                        case Dimension::HEIGHT_KEY:
                            $newName = "h";
                            /**
                             * We don't remove height because,
                             * the sizing should apply to img
                             */
                            break;
                    }

                    if ($newName == CacheMedia::CACHE_KEY && $value == CacheMedia::CACHE_DEFAULT_VALUE) {
                        // This is the default
                        // No need to add it
                        continue;
                    }

                    if (!empty($value)) {
                        $att[$newName] = trim($value);
                    }
                }

            }

            /**
             * Cache bursting
             */
            if (!$tagAttributes->hasComponentAttribute(CacheMedia::CACHE_BUSTER_KEY)) {
                $att[CacheMedia::CACHE_BUSTER_KEY] = $this->getModifiedTime();
            }

            $direct = true;
            return ml($this->getId(), $att, $direct, $ampersand, true);

        } else {

            return null;

        }
    }

    public function getAbsoluteUrl(): ?string
    {

        return $this->getUrl();

    }

}
