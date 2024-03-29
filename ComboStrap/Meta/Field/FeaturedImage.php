<?php

namespace ComboStrap\Meta\Field;

use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\PageImageUsage;
use ComboStrap\ResourceCombo;
use ComboStrap\Site;
use ComboStrap\WikiPath;

/**
 * A field that derived the featured image for the html page/blog
 *
 * (The featured image for a html page may be a svg
 * while for a social network, it should not)
 *
 * This meta returns the first svg image found
 * otherwise the raster one
 */
class FeaturedImage extends MetadataImage
{


    const PROPERTY_NAME = "featured-image";


    public static function createFromResourcePage(MarkupPath $page): FeaturedImage
    {
        return (new FeaturedImage())->setResource($page);
    }

    static public function getDescription(): string
    {
        return "The image for a page/blog";
    }

    static public function getLabel(): string
    {
        return "Featured Image";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }


    static public function isMutable(): bool
    {
        return false;
    }

    public function getValue(): WikiPath
    {

        $contextPage = $this->getResource();
        return $this->getFeaturedImageBlogForContext($contextPage);

    }

    /**
     * The image may not be the first otherwise, it will make a duplicate
     * @param ResourceCombo $contextPage
     * @return WikiPath
     * @throws ExceptionNotFound
     */
    private function getFeaturedImageBlogForContext(ResourceCombo $contextPage): WikiPath
    {
        $featuredSvgImage = FeaturedSvgImage::createFromResourcePage($contextPage);
        $featuredRasterImage = FeaturedRasterImage::createFromResourcePage($contextPage);
        try {
            return $featuredSvgImage->getValue();
        } catch (ExceptionNotFound $e) {
            try {
                return $featuredRasterImage->getValue();
            } catch (ExceptionNotFound $e) {
                try {
                    return $featuredSvgImage->getDefaultValue();
                } catch (ExceptionNotFound $e) {
                    return $featuredRasterImage->getDefaultValue();
                }
            }
        }
    }

    static public function getDrive(): string
    {
        return WikiPath::MEDIA_DRIVE;
    }

    static public function isOnForm(): bool
    {
        return true;
    }

    public static function getCanonical(): string
    {
        return self::PROPERTY_NAME;
    }


}
