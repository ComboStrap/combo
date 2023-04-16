<?php

namespace ComboStrap;

use ComboStrap\Meta\Field\FeaturedRasterImage;
use ComboStrap\Meta\Field\FeaturedSvgImage;
use Exception;

class ImageSystem
{

    /**
     * @throws ExceptionNotFound - if the image was not found
     */
    public static function selectAndGetBestMetadataPageImageFetcherForRatio(MarkupPath $page, TagAttributes $tagAttributes): IFetcherLocalImage
    {
        /**
         * Take the image and the page images
         * of the first page with an image
         */
        $selectedPageImage = self::createImageFetchFromPageImageMetadata($page);
        $stringRatio = $tagAttributes->getValue(Dimension::RATIO_ATTRIBUTE);
        if ($stringRatio === null) {
            return $selectedPageImage;
        }

        /**
         * We select the best image for the ratio
         * Best ratio
         */
        $bestRatioDistance = 9999;
        try {
            $targetRatio = Dimension::convertTextualRatioToNumber($stringRatio);
        } catch (ExceptionBadSyntax $e) {
            LogUtility::error("The ratio ($stringRatio) is not a valid ratio. Error: {$e->getMessage()}", PageImageTag::CANONICAL);
            return $selectedPageImage;
        }

        $pageImages = $page->getPageMetadataImages();
        foreach ($pageImages as $pageImage) {
            $path = $pageImage->getImagePath();
            try {
                $fetcherImage = IFetcherLocalImage::createImageFetchFromPath($path);
            } catch (Exception $e) {
                LogUtility::msg("An image object could not be build from ($path). Is it an image file ?. Error: {$e->getMessage()}");
                continue;
            }
            $ratioDistance = $targetRatio - $fetcherImage->getIntrinsicAspectRatio();
            if ($ratioDistance < $bestRatioDistance) {
                $bestRatioDistance = $ratioDistance;
                $selectedPageImage = $fetcherImage;
            }
        }
        return $selectedPageImage;
    }

    /**
     * @throws ExceptionNotFound
     * @deprecated
     */
    public static function createImageFetchFromPageImageMetadata(MarkupPath $page): IFetcherLocalImage
    {
        $selectedPageImage = null;
        foreach ($page->getPageMetadataImages() as $pageMetadataImage) {
            try {
                $pageMetadataImagePath = $pageMetadataImage->getImagePath();
                $selectedPageImage = IFetcherLocalImage::createImageFetchFromPath($pageMetadataImagePath);
            } catch (\Exception $e) {
                LogUtility::internalError("The file ($pageMetadataImagePath) is not a valid image for the page ($page). Error: {$e->getMessage()}");
                continue;
            }
        }
        if ($selectedPageImage !== null) {
            return $selectedPageImage;
        }
        throw new ExceptionNotFound("No page image metadata image could be found for the page ($page)");
    }


}
