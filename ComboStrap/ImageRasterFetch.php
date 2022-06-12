<?php


namespace ComboStrap;

/**
 * A Image Raster process class that can output:
 *   * an URL for an HTTP request
 *   * or a file for an HTTP response or further local processing
 *
 *
 */
class ImageRasterFetch extends ImageFetch
{

    const CANONICAL = "raster";
    private DokuPath $path;
    private Mime $mime;


    /**
     * @param $path
     * @throws ExceptionBadArgument - if the path is not an image
     * @throws ExceptionBadSyntax - if the image is badly encoded
     * @throws ExceptionNotExists - if the image does not exists
     * @throws ExceptionNotFound - if the mime was not found
     */
    public function __construct($path)
    {
        if ($path instanceof DokuPath) {
            $this->path = $path;
        } else {
            $this->path = DokuPath::createFromPath($path);
        }
        $this->analyzeImageIfNeeded();
        $this->mime = FileSystems::getMime($path);
        parent::__construct();
    }

    private int $imageWidth;
    private int $imageWeight;


    /**
     * @param string $imageId
     * @param null $rev
     * @return ImageRasterFetch
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     */
    public static function createImageRasterFetchFromId(string $imageId, $rev = null): ImageRasterFetch
    {
        return new ImageRasterFetch(DokuPath::createMediaPathFromId($imageId, $rev));
    }

    /**
     * @param Path $path
     * @return ImageRasterFetch
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     */
    public static function createImageRasterFetchFromPath(Path $path): ImageRasterFetch
    {
        return new ImageRasterFetch($path);
    }


    /**
     * @return int - the width of the image from the file
     */
    public function getIntrinsicWidth(): int
    {
        return $this->imageWidth;
    }

    /**
     * @return int - the height of the image from the file
     */
    public function getIntrinsicHeight(): int
    {
        return $this->imageWeight;
    }

    /**
     *
     * @throws ExceptionBadSyntax - if the path is not valid image format
     * @throws ExceptionNotExists - if the image does not exists
     */
    private
    function analyzeImageIfNeeded()
    {

        if (!FileSystems::exists($this->path)) {
            throw new ExceptionNotExists("The path ({$this->path}) does not exists");
        }

        /**
         * Based on {@link media_image_preview_size()}
         * $dimensions = media_image_preview_size($this->id, '', false);
         */
        $path = $this->path->toLocalPath();
        $imageSize = getimagesize($path->toAbsolutePath()->toPathString());
        if ($imageSize === false) {
            throw new ExceptionBadSyntax("We couldn't retrieve the type and dimensions of the image ($this). The image format seems to be not supported.", self::CANONICAL);
        }
        $this->imageWidth = (int)$imageSize[0];
        if (empty($this->imageWidth)) {
            throw new ExceptionBadSyntax("We couldn't retrieve the width of the image ($this)", self::CANONICAL);
        }
        $this->imageWeight = (int)$imageSize[1];
        if (empty($this->imageWeight)) {
            throw new ExceptionBadSyntax("We couldn't retrieve the height of the image ($this)", self::CANONICAL);
        }

    }


    /**
     *
     */
    public function getFetchUrl(): Url
    {

        try {
            $fetchUrl = DokuFetch::createFromPath($this->path)->getFetchUrl();
        } catch (ExceptionNotFound $e) {
            throw new ExceptionRuntime("The image exists. This is already checked at construction");
        }

        /**
         * If the request is not the original image
         * and not cropped, add the width and height
         */
        try {
            $targetWidth = $this->getTargetWidth();
            if ($targetWidth !== $this->getIntrinsicWidth()) {
                $fetchUrl->addQueryParameter("w", $this->getTargetHeight());
            }
        } catch (ExceptionBadArgument|ExceptionBadSyntax|ExceptionNotExists $e) {
            // no target width
        }

        $targetHeight = $this->getTargetHeight();
        if ($targetHeight !== $this->getIntrinsicHeight()) {
            $fetchUrl->addQueryParameter("h", $this->getTargetHeight());
        }

        if (!empty($this->getRequestedCache())) {
            $fetchUrl->addQueryParameter(ImageFetch::CACHE_KEY, $this->getRequestedCache());
        }
        return $fetchUrl;

    }


    /**
     * We overwrite the {@link ImageFetch::getTargetWidth()}
     * because we don't scale up for raster image
     * to not lose quality.
     *
     * @return int
     * @throws ExceptionBadArgument - if the requested width is not valid
     * @throws ExceptionBadSyntax - if the image is not a raster image and the intrinsic width is then unknown
     * @throws ExceptionNotExists - if the image does not exists
     */
    public
    function getTargetWidth(): int
    {

        try {
            $requestedWidth = $this->getRequestedWidth();
        } catch (ExceptionBadArgument|ExceptionNotFound $e) {
            return parent::getTargetWidth();
        }


        // it should not be bigger than the media Height
        $mediaWidth = $this->getIntrinsicWidth();
        if ($requestedWidth > $mediaWidth) {
            global $ID;
            if ($ID !== "wiki:syntax") {
                // There is a bug in the wiki syntax page
                // {{wiki:dokuwiki-128.png?200x50}}
                // https://forum.dokuwiki.org/d/19313-bugtypo-how-to-make-a-request-to-change-the-syntax-page-on-dokuwikii
                LogUtility::msg("For the image ($this), the requested width of ($requestedWidth) can not be bigger than the intrinsic width of ($mediaWidth). The width was then set to its natural width ($mediaWidth)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            }
            return $mediaWidth;
        }

        return parent::getTargetWidth();

    }


    /**
     *
     */
    public
    function getTargetHeight(): int
    {

        try {
            $requestedHeight = $this->getRequestedHeight();

            // it should not be bigger than the media Height
            try {
                $mediaHeight = $this->getIntrinsicHeight();
            } catch (ExceptionBadSyntax $e) {
                return parent::getTargetHeight();
            }
            if ($requestedHeight > $mediaHeight) {
                LogUtility::info("For the image ($this), the requested height of ($requestedHeight) can not be bigger than the intrinsic height of ($mediaHeight). The height was then set to its natural height ($mediaHeight)", self::CANONICAL);
                return $mediaHeight;
            }
        } catch (ExceptionBadArgument|ExceptionNotFound $e) {
            // no request height
        }
        return parent::getTargetHeight();


    }


    function getFetchPath(): Path
    {
        throw new ExceptionRuntime("Fetch Raster image is not yet implemented");
    }

    function acceptsFetchUrl(Url $url): bool
    {
        // dokuwiki do it for now
        return false;
    }

    function getBuster(): string
    {
        try {
            return FileSystems::getCacheBuster($this->path);
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("The fact that the file exists, is already checked at construction time, it should not happen", self::CANONICAL);
            return strval((new \DateTime())->getTimestamp());
        }
    }

    public function getMime(): Mime
    {
        return $this->mime;
    }

    /**
     * @return Path - the path of the original file
     */
    public function getPath():Path
    {
        return $this->path;
    }

}
