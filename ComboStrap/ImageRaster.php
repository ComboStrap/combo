<?php


namespace ComboStrap;

/**
 * TODO: What messed up is messed up
 *   This class should only wrap up the gd library
 *   to get information about the image
 *   But has also {@link RasterImageLink function} such as {@link ImageRaster::getUrlAtBreakpoint()}
 *   and {@link ImageRaster::getTargetHeight()}
 */
class ImageRaster extends Image
{

    const CANONICAL = "raster";


    public function __construct($path, $attributes = null)
    {
        parent::__construct($path, $attributes);
        $this->getAttributes()->setLogicalTag(self::CANONICAL);
    }

    private $imageWidth = null;
    /**
     * @var int
     */
    private $imageWeight = null;

    private $wasAnalyzed = false;




    /**
     * @return int - the width of the image from the file
     * @throws ExceptionCompile
     */
    public function getIntrinsicWidth(): int
    {
        $this->analyzeImageIfNeeded();
        return $this->imageWidth;
    }

    /**
     * @return int - the height of the image from the file
     * @throws ExceptionCompile
     */
    public function getIntrinsicHeight(): int
    {
        $this->analyzeImageIfNeeded();
        return $this->imageWeight;
    }

    /**
     * @throws ExceptionCompile
     */
    private
    function analyzeImageIfNeeded()
    {

        if (!$this->wasAnalyzed) {

            if ($this->exists()) {

                /**
                 * Based on {@link media_image_preview_size()}
                 * $dimensions = media_image_preview_size($this->id, '', false);
                 */
                $path = $this->getPath();
                if ($path instanceof DokuPath) {
                    $path = $path->toLocalPath();
                }
                $imageSize = getimagesize($path->toAbsolutePath()->toPathString());
                if ($imageSize === false) {
                    throw new ExceptionCompile("We couldn't retrieve the type and dimensions of the image ($this). The image format seems to be not supported.", self::CANONICAL);
                }
                $this->imageWidth = (int)$imageSize[0];
                if (empty($this->imageWidth)) {
                    throw new ExceptionCompile("We couldn't retrieve the width of the image ($this)", self::CANONICAL);
                }
                $this->imageWeight = (int)$imageSize[1];
                if (empty($this->imageWeight)) {
                    throw new ExceptionCompile("We couldn't retrieve the height of the image ($this)", self::CANONICAL);
                }

            }
        }
        $this->wasAnalyzed = true;
    }


    /**
     * @throws ExceptionCompile
     */
    public function getUrl()
    {
        return $this->getUrlAtBreakpoint();
    }


    /**
     * @param int|null $breakpointWidth - the breakpoint width - use for responsive image
     * @return string|null
     * @throws ExceptionCompile
     */
    public function getUrlAtBreakpoint(int $breakpointWidth = null)
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


        if ($this->getPath() === null) {
            LogUtility::msg("The Url of a image not in the media library is not yet supported", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return "";
        }
        return $this->getPath()->getUrl($att);


    }

    /**
     * @throws ExceptionCompile
     */
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
     * @return int
     * @throws ExceptionCompile
     */
    public
    function getTargetWidth(): int
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

    /**
     * @throws ExceptionCompile
     */
    public function getTargetHeight(): int
    {

        $requestedHeight = $this->getRequestedHeight();
        if (!empty($requestedHeight)) {
            // it should not be bigger than the media Height
            $mediaHeight = $this->getIntrinsicHeight();
            if (!empty($mediaHeight)) {
                if ($requestedHeight > $mediaHeight) {
                    LogUtility::info("For the image ($this), the requested height of ($requestedHeight) can not be bigger than the intrinsic height of ($mediaHeight). The height was then set to its natural height ($mediaHeight)", self::CANONICAL);
                    return $mediaHeight;
                }
            }
        }

        return parent::getTargetHeight();
    }


}
