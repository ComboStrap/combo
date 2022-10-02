<?php


namespace ComboStrap;

/**
 *
 * A Image Raster processing class that:
 *   * takes as input:
 *      * a {@link FetcherRaster::buildFromUrl() fetch URL}:
 *          * from an HTTP request
 *          * or {@link MediaMarkup::getFetchUrl() markup})
 *      * or data via setter
 *   * outputs:
 *      * a {@link FetcherRaster::getFetchPath() raster image file} for:
 *         * an HTTP response
 *         * or further local processing
 *      * or a {@link FetcherRaster::getFetchUrl() fetch url} to use in a {@link RasterImageLink img html tag}
 *
 */
class FetcherRaster extends IFetcherLocalImage
{

    use FetcherTraitWikiPath {
        setSourcePath as protected setOriginalPathTrait;
    }

    const CANONICAL = "raster";


    private int $imageWidth;
    private int $imageWeight;


    /**
     * @param string $imageId
     * @param null $rev
     * @return FetcherRaster
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public static function createImageRasterFetchFromId(string $imageId, $rev = null): FetcherRaster
    {
        return IFetcherLocalImage::createImageFetchFromPath(WikiPath::createMediaPathFromId($imageId, $rev));
    }

    /**
     * @param Path $path
     * @return FetcherRaster
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     */
    public static function createImageRasterFetchFromPath(Path $path): FetcherRaster
    {
        $path = WikiPath::createFromPathObject($path);
        return self::createEmptyRaster()
            ->setSourcePath($path);
    }

    public static function createEmptyRaster(): FetcherRaster
    {
        return new FetcherRaster();
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function createRasterFromFetchUrl(Url $fetchUrl): FetcherRaster
    {
        $fetchImageRaster = self::createEmptyRaster();
        $fetchImageRaster->buildFromUrl($fetchUrl);
        return $fetchImageRaster;
    }


    /**
     * @return int - the width of the image from the file
     */
    public function getIntrinsicWidth(): int
    {
        return $this->imageWidth;
    }

    public function getFetchUrl(Url $url = null): Url
    {

        $url = parent::getFetchUrl($url);

        /**
         * Trait
         */
        $this->addLocalPathParametersToFetchUrl($url,self::$MEDIA_QUERY_PARAMETER);

        return $url;
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

        if (!FileSystems::exists($this->getSourcePath())) {
            throw new ExceptionNotExists("The path ({$this->getSourcePath()}) does not exists");
        }

        /**
         * Based on {@link media_image_preview_size()}
         * $dimensions = media_image_preview_size($this->id, '', false);
         */
        $path = $this->getSourcePath()->toLocalPath();
        $imageSize = getimagesize($path->toAbsolutePath()->toQualifiedId());
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
     * We overwrite the {@link FetcherTraitImage::getRequestedWidth()}
     * because we don't scale up for raster image
     * to not lose quality.
     *
     * @return int
     * @throws ExceptionNotFound
     */
    public
    function getRequestedWidth(): int
    {

        /**
         * Test, requested width should not be bigger than the media Height
         * If this is the case, we return the media width
         */
        $requestedWidth = parent::getRequestedWidth();

        /**
         * A width was requested
         */
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

        return $requestedWidth;

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
            $mediaHeight = $this->getIntrinsicHeight();
            if ($requestedHeight > $mediaHeight) {
                LogUtility::warning("For the image ($this), the requested height of ($requestedHeight) can not be bigger than the intrinsic height of ($mediaHeight). The height was then set to its natural height ($mediaHeight)", self::CANONICAL);
                return $mediaHeight;
            }
        } catch (ExceptionNotFound $e) {
            // no request height
        }
        return parent::getTargetHeight();


    }


    function getFetchPath(): LocalPath
    {
        /**
         * In fetch.php
         * if($HEIGHT && $WIDTH) {
         *    $data['file'] = $FILE = media_crop_image($data['file'], $EXT, $WIDTH, $HEIGHT);
         * } else {
         *    $data['file'] = $FILE = media_resize_image($data['file'], $EXT, $WIDTH, $HEIGHT);
         * }
         */
        throw new ExceptionRuntime("Fetch Raster image is not yet implemented");
    }


    /**
     * @param TagAttributes $tagAttributes
     * @return FetcherRaster
     * @throws ExceptionBadArgument - if the path is not an image
     * @throws ExceptionBadSyntax - if the image is badly encoded
     * @throws ExceptionNotExists - if the image does not exists
     */

    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetcherImage
    {

        parent::buildFromTagAttributes($tagAttributes);
        $this->buildOriginalPathFromTagAttributes($tagAttributes);
        $this->analyzeImageIfNeeded();
        return $this;

    }

    /**
     * @throws ExceptionBadSyntax - if the file is badly encoded
     * @throws ExceptionNotExists - if the file does not exists
     */
    public function setSourcePath(WikiPath $path): FetcherRaster
    {
        $this->setOriginalPathTrait($path);
        $this->analyzeImageIfNeeded();
        return $this;
    }


    public function getFetcherName(): string
    {
        return self::CANONICAL;
    }
}
