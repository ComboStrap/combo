<?php


namespace ComboStrap;

use ComboStrap\Web\Url;

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
    const FAKE_LENGTH_FOR_BROKEN_IMAGES = 10;


    private int $imageWidth;
    private int $imageWeight;


    /**
     * @param string $imageId
     * @param null $rev
     * @return FetcherRaster
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     */
    public static function createImageRasterFetchFromId(string $imageId, $rev = null): FetcherRaster
    {
        return FetcherRaster::createImageRasterFetchFromPath(WikiPath::createMediaPathFromId($imageId, $rev));
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
        /**
         *
         * Because {@link FetcherRaster} does not create the image itself
         * but dokuwiki does, we need to add the with and height dimension
         * if the ratio is asked
         *
         * Before all other parent requirement such as
         * ({@link FetcherImage::getTok()} uses them
         *
         * Note that we takes the target value
         * before setting them otherwise it will affect the calculcation
         * ie if we set the height and then calculatiing the target width, we will get
         * a mini difference
         *
         */
        try {
            $this->getRequestedAspectRatio();
            $targetHeight = $this->getTargetHeight();
            $targetWidth = $this->getTargetWidth();
            $this->setRequestedWidth($targetWidth);
            $this->setRequestedHeight($targetHeight);
        } catch (ExceptionNotFound $e) {
            //
        }

        $url = parent::getFetchUrl($url);

        /**
         * Trait
         */
        $this->addLocalPathParametersToFetchUrl($url, MediaMarkup::$MEDIA_QUERY_PARAMETER);

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
     * We check the existence of the file at build time
     * because when we build the url, we make it at several breakpoints.
     * We therefore needs the intrinsic dimension (height and weight)
     *
     * @throws ExceptionBadSyntax - if the path is not valid image format
     */
    private
    function analyzeImageIfNeeded()
    {

        if (!FileSystems::exists($this->getSourcePath())) {
            // The user may type a bad path
            // We don't throw as we want to be able to build
            LogUtility::warning("The path ({$this->getSourcePath()}) does not exists");
            // broken image in the browser does not have any dimension
            // todo: https://bitsofco.de/styling-broken-images/
            $this->imageWidth = self::FAKE_LENGTH_FOR_BROKEN_IMAGES;
            $this->imageWeight = self::FAKE_LENGTH_FOR_BROKEN_IMAGES;
            return;
        }

        /**
         * Based on {@link media_image_preview_size()}
         * $dimensions = media_image_preview_size($this->id, '', false);
         */
        $path = $this->getSourcePath()->toLocalPath();
        $imageSize = getimagesize($path->toAbsolutePath()->toAbsoluteId());
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
                /**
                 * Info and not warning level because they fill the error log
                 * They don't really break anything and it's difficult
                 * to see when it's intended (ie there is no better image or not)
                 */
                // There is a bug in the wiki syntax page
                // {{wiki:dokuwiki-128.png?200x50}}
                // https://forum.dokuwiki.org/d/19313-bugtypo-how-to-make-a-request-to-change-the-syntax-page-on-dokuwikii
                LogUtility::info("For the image ($this), the requested width of ($requestedWidth) can not be bigger than the intrinsic width of ($mediaWidth). The width was then set to its natural width ($mediaWidth)", self::CANONICAL);
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
                /**
                 * Info and not warning level because they fill the error log
                 * They don't really break anything and it's difficult
                 * to see when it's intended (ie there is no better image or not)
                 */
                LogUtility::info("For the image ($this), the requested height of ($requestedHeight) can not be bigger than the intrinsic height of ($mediaHeight). The height was then set to its natural height ($mediaHeight)", self::CANONICAL);
                return $mediaHeight;
            }
        } catch (ExceptionNotFound $e) {
            // no request height
        }
        return parent::getTargetHeight();


    }

    public function getTargetWidth(): int
    {
        $targetWidth = parent::getTargetWidth();
        $intrinsicWidth = $this->getIntrinsicWidth();
        if ($targetWidth > $intrinsicWidth) {
            /**
             * Info and not warning level because they fill the error log
             * They don't really break anything and it's difficult
             * to see when it's intended (ie there is no better image or not)
             */
            LogUtility::debug("For the image ($this), the calculated width of ($targetWidth) cannot be bigger than the intrinsic width of ($targetWidth). The requested width was then set to its natural width ($intrinsicWidth).", self::CANONICAL);
            return $intrinsicWidth;
        }
        return $targetWidth;
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

    public function __toString()
    {
        return $this->getSourcePath()->__toString();
    }

    /**
     * @return int
     * @throws ExceptionNotFound
     * We can upscale, we limit then the requested height to the internal size
     */
    public function getRequestedHeight(): int
    {
        $requestedHeight = parent::getRequestedHeight();
        $intrinsicHeight = $this->getIntrinsicHeight();
        if ($requestedHeight > $intrinsicHeight) {
            /**
             * Info and not warning to not fill the log
             * as it's pretty common with a {@link PageImageTag}
             */
            LogUtility::info("For the image ($this), the requested height of ($requestedHeight) can not be bigger than the intrinsic height of ($intrinsicHeight). The height was then set to its natural height ($intrinsicHeight)", self::CANONICAL);
            return $intrinsicHeight;
        }
        return $requestedHeight;
    }


}
