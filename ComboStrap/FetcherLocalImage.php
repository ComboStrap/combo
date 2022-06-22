<?php

namespace ComboStrap;

/**
 * A local image
 *
 * This was to check for the social image
 * in a list of image if the image was also the first image
 * based on the path.
 */
abstract class FetcherLocalImage extends FetcherAbs implements FetcherImage
{

    /**
     * @param DokuPath $path
     * @return FetcherRaster|FetcherSvg
     * @throws ExceptionBadArgument - if the path is not an image
     */
    public static function createImageFetchFromPath(DokuPath $path): FetcherLocalImage
    {

        try {
            $mime = FileSystems::getMime($path);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionBadArgument("The file ($path) has an unknown mime, we can't verify if we support it", FetcherImage::CANONICAL_IMAGE);
        }

        if (!$mime->isImage()) {
            throw new ExceptionBadArgument("The file ($path) has not been detected as being an image, media returned", FetcherImage::CANONICAL_IMAGE);
        }

        if ($mime->toString() === Mime::SVG) {

            $image = FetcherSvg::createSvgFromPath($path);

        } else {

            $image = FetcherRaster::createImageRasterFetchFromPath($path);

        }


        return $image;


    }

    /**
     * @param string $imageId
     * @param string|null $rev
     * @return FetcherLocalImage
     * @throws ExceptionBadArgument - if the path is not an image
     */
    public static function createImageFetchFromId(string $imageId, string $rev = null): FetcherLocalImage
    {
        $dokuPath = DokuPath::createMediaPathFromId($imageId, $rev);
        return FetcherLocalImage::createImageFetchFromPath($dokuPath);
    }

    abstract public function getOriginalPath(): DokuPath;


    function getBuster(): string
    {
        try {
            return FileSystems::getCacheBuster($this->getOriginalPath());
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("The fact that the file exists, is already checked at construction time, it should not happen", FetcherImage::CANONICAL_IMAGE);
            return strval((new \DateTime())->getTimestamp());
        }
    }

}
