<?php

namespace ComboStrap\Meta\Field;

use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\PageImageUsage;
use ComboStrap\Site;
use ComboStrap\WikiPath;

/**
 * A field that derived the featured image for the page
 * ie with Svg first
 */
class FeaturedImagePage extends MetadataImage
{


    const PROPERTY_NAME = "feature-image-page";

    public static function createFromResourcePage(MarkupPath $page): FeaturedImagePage
    {
        return (new FeaturedImagePage())->setResource($page);
    }

    public function getDescription(): string
    {
        return "The featured image for a page/blog";
    }

    public function getLabel(): string
    {
        return "Featured Image";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }


    public function getMutable(): bool
    {
        return false;
    }

    public function getValue(): WikiPath
    {

        $contextPage = $this->getResource();
        $featuredSvgImage = FeaturedSvgImage::createFromResourcePage($contextPage);
        $featuredRasterImage = FeaturedRasterImage::createFromResourcePage($contextPage);
        try {
            return $featuredSvgImage->getValueOrParsed();
        } catch (ExceptionNotFound $e) {
            return $featuredRasterImage->getValueOrParsed();
        }

    }


}
