<?php


namespace ComboStrap;


class ImageRaster extends Image
{

    public function __construct($absolutePath, $rev = null)
    {
        parent::__construct($absolutePath, DokuPath::MEDIA_TYPE, $rev);
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
    public
    function getWidth(): ?int
    {
        $this->analyzeImageIfNeeded();
        return $this->imageWidth;
    }

    /**
     * @return int - the height of the image from the file
     */
    public
    function getHeight()
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
    public
    function isAnalyzable()
    {
        $this->analyzeImageIfNeeded();
        return $this->analyzable;

    }



}
