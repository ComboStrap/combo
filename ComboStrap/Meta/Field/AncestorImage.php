<?php

namespace ComboStrap\Meta\Field;

use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\PageImageTag;
use ComboStrap\PageImageUsage;
use ComboStrap\ResourceCombo;
use ComboStrap\Site;
use ComboStrap\WikiPath;

/**
 * Retrieve the featured image of the ancestor
 *
 * Can be used in a {@link PageImageTag}
 */
class AncestorImage extends MetadataImage
{


    const PROPERTY_NAME = "ancestor-image";

    public static function createFromResourcePage(MarkupPath $page): AncestorImage
    {
        return (new AncestorImage())->setResource($page);
    }

    public function getDescription(): string
    {
        return "The featured image from the closest ancestor page";
    }

    public function getLabel(): string
    {
        return "Ancestor Image";
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
                $actual = $actual->getParent();
            } catch (ExceptionNotFound $e) {
                break;
            }
            try {
                return FeaturedImage::createFromResourcePage($actual)->getValue();
            } catch (ExceptionNotFound $e) {
                // ok
            }
        }
        throw new ExceptionNotFound();

    }


    public function getDrive(): string
    {
        return WikiPath::MEDIA_DRIVE;
    }


}
