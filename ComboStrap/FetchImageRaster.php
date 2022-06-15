<?php


namespace ComboStrap;

/**
 * A Image Raster process class that can output:
 *   * an URL for an HTTP request
 *   * or a file for an HTTP response or further local processing
 *
 *
 */
class FetchImageRaster extends FetchImage
{

    const CANONICAL = "raster";
    private ?DokuPath $originalPath = null;
    private Mime $mime;


    private int $imageWidth;
    private int $imageWeight;


    /**
     * @param string $imageId
     * @param null $rev
     * @return FetchImageRaster
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     */
    public static function createImageRasterFetchFromId(string $imageId, $rev = null): FetchImageRaster
    {
        return new FetchImageRaster(DokuPath::createMediaPathFromId($imageId, $rev));
    }

    /**
     * @param Path $path
     * @return FetchImageRaster
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     */
    public static function createImageRasterFetchFromPath(Path $path): FetchImageRaster
    {
        return new FetchImageRaster($path);
    }

    public static function createEmptyRaster(): FetchImageRaster
    {
        return new FetchImageRaster();
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public static function createRasterFromFetchUrl(Url $fetchUrl): FetchImageRaster
    {
        return self::createEmptyRaster()
            ->buildFromUrl($fetchUrl);
    }

    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public static function createRasterFromMediaMarkup(MediaMarkup $mediaMarkup): FetchImageRaster
    {
        return self::createRasterFromFetchUrl($mediaMarkup->getFetchUrl());
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

        if (!FileSystems::exists($this->originalPath)) {
            throw new ExceptionNotExists("The path ({$this->originalPath}) does not exists");
        }

        /**
         * Based on {@link media_image_preview_size()}
         * $dimensions = media_image_preview_size($this->id, '', false);
         */
        $path = $this->originalPath->toLocalPath();
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


    public function getFetchUrl(Url $url = null): Url
    {

        $fetchUrl = FetchDoku::createFromPath($this->originalPath)->getFetchUrl($url);
        $this->addCommonImageQueryParameterToUrl($fetchUrl);
        return $fetchUrl;

    }


    /**
     * We overwrite the {@link FetchImage::getTargetWidth()}
     * because we don't scale up for raster image
     * to not lose quality.
     *
     * @return int
     */
    public
    function getTargetWidth(): int
    {

        try {
            $requestedWidth = $this->getRequestedWidth();
        } catch (ExceptionNotFound $e) {
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
        } catch (ExceptionNotFound $e) {
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
            return FileSystems::getCacheBuster($this->originalPath);
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
     * @return DokuPath - the path of the original svg if any
     * @throws ExceptionNotFound - not used
     */
    public function getOriginalPath(): DokuPath
    {
        if ($this->originalPath === null) {
            throw new ExceptionNotFound("No original path");
        }
        return $this->originalPath;
    }

    /**
     * @param Url $url
     * @return FetchImageRaster
     * @throws ExceptionBadArgument - if the path is not an image
     * @throws ExceptionBadSyntax - if the image is badly encoded
     * @throws ExceptionNotExists - if the image does not exists
     * @throws ExceptionNotFound - if the mime was not found
     */

    public function buildFromUrl(Url $url): FetchImageRaster
    {
        $this->originalPath = FetchDoku::createEmpty()->buildFromUrl($url)->getFetchPath();
        $this->analyzeImageIfNeeded();
        $this->mime = FileSystems::getMime($this->originalPath);
        $this->addCommonImageQueryParameterToUrl($url);
        return $this;

    }


}
