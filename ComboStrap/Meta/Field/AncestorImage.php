<?php

namespace ComboStrap\Meta\Field;

use ComboStrap\ExceptionNotFound;
use ComboStrap\FirstImage;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\PageImageTag;
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

    static public function getDescription(): string
    {
        return "The featured image from the closest ancestor page";
    }

    static public function getLabel(): string
    {
        return "Ancestor Image";
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
            try {
                /**
                 * If this is a index page,
                 * the first image is generally a prominent image
                 */
                return FirstImage::createForPage($actual)->getValue();
            } catch (ExceptionNotFound $e) {
                // ok
            }
        }
        throw new ExceptionNotFound();

    }


    static public function getDrive(): string
    {
        return WikiPath::MEDIA_DRIVE;
    }


    static public function isOnForm(): bool
    {
        return true;
    }
}
