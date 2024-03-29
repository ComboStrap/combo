<?php

namespace ComboStrap;

/**
 *
 * This class represents a fetcher that sends back a local image processed or not.
 *
 */
abstract class IFetcherLocalImage extends FetcherImage implements IFetcherSource
{

    /**
     * @param WikiPath $path
     * @return IFetcherLocalImage
     * @throws ExceptionBadArgument - if the path is not an image
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     */
    public static function createImageFetchFromPath(WikiPath $path): IFetcherLocalImage
    {

        try {
            $mime = FileSystems::getMime($path);
        } catch (ExceptionNotFound $e) {
            throw new ExceptionBadArgument("The file ($path) has an unknown mime, we can't verify if we support it", FetcherImage::CANONICAL);
        }

        if (!$mime->isImage()) {
            throw new ExceptionBadArgument("The file ($path) has not been detected as being an image, media returned", FetcherImage::CANONICAL);
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
     * @return IFetcherLocalImage
     * @throws ExceptionBadArgument - if the path is not an image
     */
    public static function createImageFetchFromId(string $imageId, string $rev = null): IFetcherLocalImage
    {
        $dokuPath = WikiPath::createMediaPathFromId($imageId, $rev);
        return IFetcherLocalImage::createImageFetchFromPath($dokuPath);
    }


    function getBuster(): string
    {
        try {
            return FileSystems::getCacheBuster($this->getSourcePath());
        } catch (ExceptionNotFound $e) {
            // file does not exists
            return strval((new \DateTime())->getTimestamp());
        }
    }

    public function getLabel(): string
    {

        $sourcePath = $this->getSourcePath();
        return ResourceName::getFromPath($sourcePath);

    }


}
