<?php

namespace ComboStrap\Meta\Field;

use ComboStrap\ExceptionNotFound;
use ComboStrap\MarkupPath;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataImage;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use ComboStrap\Site;
use ComboStrap\WikiPath;


class FeaturedSvgImage extends MetadataImage
{

    const PROPERTY_NAME = "featured-svg-image";
    const FEATURED_IMAGE_PARSED = "featured-svg-image-parsed";

    private static function getComboStrapSvgLogo(): WikiPath
    {
        return WikiPath::createComboResource(":images:logo.svg");
    }

    public static function createFromResourcePage(MarkupPath $markupPath): FeaturedSvgImage
    {
        return (new FeaturedSvgImage())->setResource($markupPath);
    }

    public function getDescription(): string
    {
        return "A featured image in svg format";
    }

    public function getLabel(): string
    {
        return "Featured Svg Image";
    }

    public static function getName(): string
    {
        return "featured-svg-image";
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }


    public function setParsedValue(WikiPath $path): FeaturedSvgImage
    {
        $store = $this->getWriteStore();
        if ($store instanceof MetadataDokuWikiStore) {
            $store->setFromPersistentName(self::FEATURED_IMAGE_PARSED, $path->toAbsoluteString());
        }
        return $this;
    }

    public function getDefaultValue(): WikiPath
    {

        /**
         * Parsed Feature Images
         */
        try {
            return WikiPath::createMediaPathFromPath($this->getParsedValue());
        } catch (ExceptionNotFound $e) {
            // ok
        }


        /**
         * Ancestor
         */
        $parent = $this->getResource();
        while (true) {
            try {
                $parent = $parent->getParent();
            } catch (ExceptionNotFound $e) {
                // no parent
                break;
            }
            try {
                return FeaturedSvgImage::createFromResourcePage($parent)->getValue();
            } catch (ExceptionNotFound $e) {
                continue;
            }
        }

        try {
            return Site::getLogoAsSvgImage();
        } catch (ExceptionNotFound $e) {
            return self::getComboStrapSvgLogo();
        }

    }

    /**
     * @throws ExceptionNotFound
     */
    public function getParsedValue(): WikiPath
    {
        $parsedValue = $this->getReadStore()->getFromPersistentName(self::FEATURED_IMAGE_PARSED);
        if($parsedValue===null){
            throw new ExceptionNotFound();
        }
        return WikiPath::createMediaPathFromPath($parsedValue);
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getValueOrParsed(): WikiPath
    {
        try {
            return $this->getValue();
        } catch (ExceptionNotFound $e) {
            return $this->getParsedValue();
        }
    }


}
