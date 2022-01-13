<?php


namespace ComboStrap;

/**
 * Class ImageSvg
 * @package ComboStrap
 * A svg image
 *
 * TODO: implements {@link CachedDocument} ? to not cache the optimization in {@link ImageSvg::getSvgFile()}
 */
class ImageSvg extends Image
{

    const EXTENSION = "svg";
    const CANONICAL = "svg";


    /**
     * @throws ExceptionCombo
     */
    public function __construct($path, $tagAttributes = null)
    {

        /**
         * We build the svg document immediately to validate it
         * Getting the width of an error HTML document if the file was downloaded
         * from a server has no use at all
         */
        $this->svgDocument = SvgDocument::createSvgDocumentFromPath($path);
        parent::__construct($path, $tagAttributes);
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


    protected function getSvgDocument(): SvgDocument
    {
        return $this->svgDocument;
    }

    /**
     * @param string $ampersand $absolute - the & separator (should be encoded for HTML but not for CSS)
     * @return string|null
     *
     * At contrary to {@link RasterImageLink::getUrl()} this function does not need any width parameter
     */
    public function getUrl(string $ampersand = DokuwikiUrl::AMPERSAND_URL_ENCODED_FOR_HTML): ?string
    {


        if (!$this->exists()) {
            LogUtility::msg("The svg media does not exist ({$this})", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return "";
        }

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

        if ($this->getPath() === null) {
            LogUtility::msg("The Url of a image not in the media library is not yet supported", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return "";
        }

        /**
         * Old model where all parameters are parsed
         * and src is not given entirely to the renderer
         * path may be still present
         */
        if (isset($att[PagePath::PROPERTY_NAME])) {
            unset($att[PagePath::PROPERTY_NAME]);
        }

        return ml($this->getPath()->getDokuwikiId(), $att, $direct, $ampersand, true);


    }

    public function getAbsoluteUrl(): ?string
    {

        return $this->getUrl();

    }

    /**
     * Return the svg file transformed by the attributes
     * from cache if possible. Used when making a fetch with the URL
     * @return LocalPath
     */
    public function getSvgFile(): LocalPath
    {

        $cache = new CacheMedia($this->getPath(), $this->getAttributes());
        if (!$cache->isCacheUsable()) {
            $svgDocument = $this->getSvgDocument();
            $content = $svgDocument->getXmlText($this->getAttributes());
            $cache->storeCache($content);
        }
        return $cache->getFile();

    }

    /**
     * The buster is not based on file but the cache file
     * because the cache is configuration dependent
     * It the user changes the configuration, the svg file is generated
     * again and the browser cache should be deleted (ie the buster regenerated)
     * {@link ResourceCombo::getBuster()}
     * @return string
     */
    public function getBuster(): string
    {
        $time = FileSystems::getModifiedTime($this->getSvgFile());
        return strval($time->getTimestamp());
    }


}
