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
     * @return FetcherRaster|FetcherSvg
     * @throws ExceptionBadArgument - if the path is not an image
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

    /**
     * @throws ExceptionNotFound
     */
    public static function createImageFetchFromPageImageMetadata(MarkupPath $page)
    {
        $selectedPageImage = null;
        foreach ($page->getPageMetadataImages() as $pageMetadataImage) {
            try {
                $pageMetadataImagePath = $pageMetadataImage->getImagePath();
                $selectedPageImage = IFetcherLocalImage::createImageFetchFromPath($pageMetadataImagePath);
            } catch (ExceptionBadArgument $e) {
                LogUtility::internalError("The file ($pageMetadataImagePath) is not a valid image for the page ($page). Error: {$e->getMessage()}");
                continue;
            }
        }
        if ($selectedPageImage !== null) {
            return $selectedPageImage;
        }
        throw new ExceptionNotFound("No page image metadata image could be found for the page ($page)");
    }



    function getBuster(): string
    {
        try {
            return FileSystems::getCacheBuster($this->getSourcePath());
        } catch (ExceptionNotFound $e) {
            LogUtility::internalError("The fact that the file exists, is already checked at construction time, it should not happen", FetcherImage::CANONICAL);
            return strval((new \DateTime())->getTimestamp());
        }
    }

}
