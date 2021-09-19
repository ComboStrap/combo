<?php


namespace ComboStrap;


class ImageRaster extends Image
{

    const CANONICAL = "raster";

    public function __construct($absolutePath, $rev = null, $attributes = null)
    {
        parent::__construct($absolutePath,  $rev, $attributes);
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
    public function getWidth(): ?int
    {
        $this->analyzeImageIfNeeded();
        return $this->imageWidth;
    }

    /**
     * @return int - the height of the image from the file
     */
    public function getHeight(): ?int
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
                $imageSize = getimagesize($this->getFileSystemPath(), $imageInfo);
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

    /**
     * @param string $ampersand - do we encode & or not (in css, you do not, in html, you do)
     * @param null $requestedWidth - the asked width - use for responsive image
     * @return string|null
     */
    public function getUrl($ampersand = DokuwikiUrl::URL_ENCODED_AND, $requestedWidth = null)
    {

        if ($this->exists()) {

            /**
             * Link attribute
             */
            $att = array();

            /**
             * The image ratio is fixed
             * Width is driving the computation
             */
            if ($requestedWidth != null && $requestedWidth != $this->getWidth()) {

                $att['w'] = $requestedWidth;

                // Height for the given width
                $height = $this->getHeightValueScaledDown($requestedWidth, null);
                if (!empty($height)) {
                    $att['h'] = $height;
                    $this->checkLogicalRatioAgainstIntrinsicRatio($requestedWidth, $height);
                }


            }

            if (!empty($this->getCache())) {
                $att[CacheMedia::CACHE_KEY] = $this->getCache();
            }
            $direct = true;

            return ml($this->getId(), $att, $direct, $ampersand, true);

        } else {

            LogUtility::msg("The image ($this) does not exist, you can't ask the URL");
            return false;

        }
    }

    public function getAbsoluteUrl()
    {

        return $this->getUrl();

    }


}
