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


class FeaturedRasterImage extends MetadataImage
{


    const PROPERTY_NAME = "featured-raster-image";
    const FEATURED_IMAGE_PARSED = "featured-raster-image-parsed";

    public static function getComboStrapLogo(): WikiPath
    {
        return WikiPath::createComboResource(":images:apple-touch-icon.png");
    }

    public static function createFromResourcePage(MarkupPath $page): FeaturedRasterImage
    {
        return (new FeaturedRasterImage())->setResource($page);
    }

    public function getDescription(): string
    {
        return "A featured image in raster format";
    }

    public function getLabel(): string
    {
        return "Featured Raster Image";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function buildFromStoreValue($value): Metadata
    {

        if ($value === null) {
            $pageImages = PageImages::createForPage($this->getResource())
                ->setReadStore($this->getReadStore())
                ->getValueAsPageImages();
            foreach ($pageImages as $pageImage) {
                $wikiPath = $pageImage->getImagePath();
                try {
                    $mime = FileSystems::getMime($wikiPath);
                    if (!$mime->isSupportedRasterImage()) {
                        continue;
                    }
                } catch (ExceptionNotFound $e) {
                    continue;
                }
                $value = $wikiPath->toAbsoluteString();
                if (in_array(PageImageUsage::ALL, $pageImage->getUsages())) {
                    break;
                }
            }
        }
        return parent::buildFromStoreValue($value);

    }

    public function getMutable(): bool
    {
        return true;
    }

    public function getDefaultValue(): WikiPath
    {

        /**
         * Parsed Feature Images
         */
        try {
            return $this->getParsedValue();
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
                return FeaturedRasterImage::createFromResourcePage($parent)->getValue();
            } catch (ExceptionNotFound $e) {
                continue;
            }
        }

        try {
            return Site::getLogoAsRasterImage()->getSourcePath();
        } catch (ExceptionNotFound $e) {
            return self::getComboStrapLogo();
        }

    }

    public function setParsedValue(WikiPath $path): FeaturedRasterImage
    {
        $store = $this->getWriteStore();
        if ($store instanceof MetadataDokuWikiStore) {
            $store->setFromPersistentName(self::FEATURED_IMAGE_PARSED, $path->toAbsoluteString());
        }
        return $this;
    }

    public function getValueOrParsed()
    {
        try {
            return $this->getValue();
        } catch (ExceptionNotFound $e) {
            return $this->getParsedValue();
        }
    }

    /**
     * @throws ExceptionNotFound
     */
    private function getParsedValue(): WikiPath
    {
        $value = $this->getReadStore()->getFromPersistentName(self::FEATURED_IMAGE_PARSED);
        if($value===null){
            throw new ExceptionNotFound();
        }
        return WikiPath::createMediaPathFromPath($value);
    }

}
