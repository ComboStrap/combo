<?php

namespace ComboStrap\Meta\Field;

use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\FirstRasterImage;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\PageImageUsage;
use ComboStrap\ResourceCombo;
use ComboStrap\Site;
use ComboStrap\WikiPath;

/**
 * A field that derived the featured image for social network
 *
 * (The featured image for a html page may be a svg
 * while for a social network, it should not)
 *
 * This meta returns the first raster image found.
 */
class SocialCardImage extends MetadataImage
{


    const PROPERTY_NAME = "social-card-image";

    public static function createFromResourcePage(MarkupPath $page): SocialCardImage
    {
        return (new SocialCardImage())->setResource($page);
    }

    public function getDescription(): string
    {
        return "The image for social card";
    }

    public function getLabel(): string
    {
        return "Social Card Image";
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
        $actual = $contextPage;
        while (true) {
            try {
                return $this->getFeaturedRasterImageOrFirst($actual);
            } catch (ExceptionNotFound $e) {
                // ok, vignette ?
            }
            try {
                $actual = $actual->getParent();
            } catch (ExceptionNotFound $e) {
                break;
            }
        }
        try {
            return Site::getLogoAsRasterImage()->getSourcePath();
        } catch (ExceptionNotFound $e) {
            return  FeaturedRasterImage::getComboStrapLogo();
        }

    }

    /**
     * @throws ExceptionNotFound
     */
    private function getFeaturedRasterImageOrFirst(ResourceCombo $contextPage): WikiPath
    {
        $featuredRasterImage = FeaturedRasterImage::createFromResourcePage($contextPage);
        try {
            return $featuredRasterImage->getValueOrParsed();
        } catch (ExceptionNotFound $e) {
            return FirstRasterImage::createForPage($contextPage)->getValue();
        }
    }


}
