<?php


namespace ComboStrap;


class ImageRaster extends Image
{

    const CANONICAL = "raster";


    public function __construct($absolutePath, $attributes = null)
    {
        parent::__construct($absolutePath, $attributes);
        $this->getAttributes()->setLogicalTag(self::CANONICAL);
    }

    private $imageWidth = null;
    /**
     * @var int
     */
    private $imageWeight = null;
    /**
     * See {@link image_type_to_mime_type}
     * @var int
     */
    private $imageType;
    private $wasAnalyzed = false;

    /**
     * @var bool
     */
    private $analyzable = false;

    /**
     * @var mixed - the mime from the {@link RasterImageLink::analyzeImageIfNeeded()}
     */
    private $mime;

    /**
     * @return int - the width of the image from the file
     */
    public function getIntrinsicWidth(): ?int
    {
        $this->analyzeImageIfNeeded();
        return $this->imageWidth;
    }

    /**
     * @return int - the height of the image from the file
     */
    public function getIntrinsicHeight(): ?int
    {
        $this->analyzeImageIfNeeded();
        return $this->imageWeight;
    }

    private
    function analyzeImageIfNeeded()
    {

        if (!$this->wasAnalyzed) {

            if ($this->exists()) {

                /**
                 * Based on {@link media_image_preview_size()}
                 * $dimensions = media_image_preview_size($this->id, '', false);
                 */
                $imageInfo = array();
                $path = $this->getPath();
                if ($path instanceof DokuPath) {
                    $path = $path->toLocalPath();
                }
                $imageSize = getimagesize($path->toAbsolutePath()->toString(), $imageInfo);
                if ($imageSize === false) {
                    $this->analyzable = false;
                    LogUtility::msg("We couldn't retrieve the type and dimensions of the image ($this). The image format seems to be not supported.", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                } else {
                    $this->analyzable = true;
                    $this->imageWidth = (int)$imageSize[0];
                    if (empty($this->imageWidth)) {
                        $this->analyzable = false;
                    }
                    $this->imageWeight = (int)$imageSize[1];
                    if (empty($this->imageWeight)) {
                        $this->analyzable = false;
                    }
                    $this->imageType = (int)$imageSize[2];
                    $this->mime = $imageSize[3];
                }
            }
        }
        $this->wasAnalyzed = true;
    }


    /**
     *
     * @return bool true if we could extract the dimensions
     */
    public function isAnalyzable(): bool
    {
        $this->analyzeImageIfNeeded();
        return $this->analyzable;

    }

    public function getUrl(string $ampersand = DokuwikiUrl::AMPERSAND_URL_ENCODED)
    {
        return $this->getUrlAtBreakpoint($ampersand);
    }

    /**
     * In the HTML attribute srcset (not in the img src), if we set,
     * ```
     * http://nico.lan/_media/docs/metadata/metadata_manager.png?w=355&amp;h=176&amp;tseed=1636624852&amp;tok=af396a 355w
     * ```
     * the request is encoded by the browser one more time and the server gets understand
     *   * `&amp;&amp;h  =   176`
     *   * php create therefore the property
     *      * `&amp;h  =   176`
     *      * and note `h = 176`
     *
     * @param $breakpoint
     * @return false|string|null
     */
    public function getUrlForSrcSetAtBreakpoint($breakpoint)
    {
        if (PluginUtility::isTest()) {
            // The test library does not support ampersand character
            return $this->getUrlAtBreakpoint(DokuwikiUrl::AMPERSAND_URL_ENCODED, $breakpoint);
        }
        return $this->getUrlAtBreakpoint(DokuwikiUrl::AMPERSAND_CHARACTER, $breakpoint);
    }

    /**
     * @param string $ampersand - do we encode & or not (in css, you do not, in html, you do)
     * @param int|null $breakpointWidth - the breakpoint width - use for responsive image
     * @return string|null
     */
    public function getUrlAtBreakpoint(string $ampersand = DokuwikiUrl::AMPERSAND_URL_ENCODED, int $breakpointWidth = null)
    {

        /**
         * Default
         */
        if ($breakpointWidth == null) {
            $breakpointWidth = $this->getTargetWidth();
        }

        if (!$this->exists()) {
            LogUtility::msg("The image ($this) does not exist, you can't ask the URL", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return false;
        }

        /**
         * Link attribute
         */
        $att = array();

        /**
         * The image ratio is fixed
         * Width is driving the computation
         */
        // Height for the given width
        $breakpointHeight = $this->getBreakpointHeight($breakpointWidth);

        /**
         * If the request is not the original image
         * and not cropped, add the width and height
         */
        if ($breakpointWidth != null &&
            (
                $breakpointWidth < $this->getIntrinsicWidth()
                ||
                $breakpointHeight < $this->getIntrinsicHeight()
            )) {

            $att['w'] = $breakpointWidth;

            if (!empty($breakpointHeight)) {
                $att['h'] = $breakpointHeight;
                $this->checkLogicalRatioAgainstTargetRatio($breakpointWidth, $breakpointHeight);
            }

        }

        if (!empty($this->getCache())) {
            $att[CacheMedia::CACHE_KEY] = $this->getCache();
        }

        /**
         * Smart Cache
         */
        $this->addCacheBusterToQueryParameters($att);

        $direct = true;

        if ($this->getPath() === null) {
            LogUtility::msg("The Url of a image not in the media library is not yet supported", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return "";
        }
        return ml($this->getPath()->getDokuwikiId(), $att, $direct, $ampersand, true);


    }

    public
    function getAbsoluteUrl()
    {

        return $this->getUrl();

    }

    /**
     * We overwrite the {@link Image::getTargetWidth()}
     * because we don't scale up for raster image
     * to not lose quality.
     *
     * @return array|int|mixed|string
     */
    public
    function getTargetWidth()
    {
        $requestedWidth = $this->getRequestedWidth();

        /**
         * May be 0 (ie empty)
         */
        if (!empty($requestedWidth)) {
            // it should not be bigger than the media Height
            $mediaWidth = $this->getIntrinsicWidth();
            if (!empty($mediaWidth)) {
                if ($requestedWidth > $mediaWidth) {
                    global $ID;
                    if ($ID != "wiki:syntax") {
                        // There is a bug in the wiki syntax page
                        // {{wiki:dokuwiki-128.png?200x50}}
                        // https://forum.dokuwiki.org/d/19313-bugtypo-how-to-make-a-request-to-change-the-syntax-page-on-dokuwikii
                        LogUtility::msg("For the image ($this), the requested width of ($requestedWidth) can not be bigger than the intrinsic width of ($mediaWidth). The width was then set to its natural width ($mediaWidth)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    }
                    $requestedWidth = $mediaWidth;
                }
            }
            return $requestedWidth;
        }

        return parent::getTargetWidth();
    }

    public
    function getTargetHeight()
    {

        $requestedHeight = $this->getRequestedHeight();
        if (!empty($requestedHeight)) {
            // it should not be bigger than the media Height
            $mediaHeight = $this->getIntrinsicHeight();
            if (!empty($mediaHeight)) {
                if ($requestedHeight > $mediaHeight) {
                    LogUtility::msg("For the image ($this), the requested height of ($requestedHeight) can not be bigger than the intrinsic height of ($mediaHeight). The height was then set to its natural height ($mediaHeight)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    $requestedHeight = $mediaHeight;
                }
            }
            return $requestedHeight;
        }

        return parent::getTargetHeight();
    }


}
