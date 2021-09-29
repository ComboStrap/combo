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

    public function __construct($absolutePath, $rev = null, $tagAttributes = null)
    {
        parent::__construct($absolutePath, $rev, $tagAttributes);
    }


    /**
     * @var SvgDocument
     */
    private $svgDocument;

    public function getIntrinsicWidth()
    {
        return $this->getSvgDocument()->getMediaWidth();
    }

    public function getIntrinsicHeight()
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
    public function getUrl($ampersand = DokuwikiUrl::URL_ENCODED_AND): ?string
    {


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
            $attributes = $this->getAttributes();
            $componentAttributes = $attributes->getComponentAttributes();
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
            $this->addCacheBusterToQueryParameters($att);

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

    /**
     * Return the svg file transformed by the attributes
     * from cache if possible. Used when making a fetch with the URL
     * @return File
     */
    public function getSvgFile(): File
    {

        $cache = new CacheMedia($this, $this->getAttributes());
        if (!$cache->isCacheUsable()) {
            $content = $this->getSvgDocument()->getXmlText($this->getAttributes());
            $cache->storeCache($content);
        }
        return File::createFromPath($cache->getFile()->getFileSystemPath());

    }

    /**
     * The buster is based on the cache file
     * because the cache is configuration dependend
     * It the user changes the configuration, the svg file is generated
     * again and the browser cache should be deleted (ie the buster regenerated)
     * @return string
     */
    public function getBuster(): string
    {
        return  $this->getSvgFile()->getBuster();
    }


}
