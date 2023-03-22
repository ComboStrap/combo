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

    static public function getDescription(): string
    {
        return "A featured image in raster format";
    }

    static public function getLabel(): string
    {
        return "Featured Raster Image";
    }

    public static function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    static public function getPersistenceType(): string
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
                $value = $wikiPath->toAbsoluteId();
                if (in_array(PageImageUsage::ALL, $pageImage->getUsages())) {
                    break;
                }
            }
        }
        return parent::buildFromStoreValue($value);

    }

    static public function isMutable(): bool
    {
        return true;
    }

    public function getDefaultValue(): WikiPath
    {

        /**
         * Parsed Feature Images
         */
        return $this->getParsedValue();


    }

    public function setParsedValue(WikiPath $path): FeaturedRasterImage
    {
        $store = $this->getWriteStore();
        if ($store instanceof MetadataDokuWikiStore) {
            $store->setFromPersistentName(self::FEATURED_IMAGE_PARSED, $path->toAbsoluteId());
        }
        return $this;
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

    /**
     * @throws ExceptionNotFound
     */
    private function getParsedValue(): WikiPath
    {
        /**
         * @var MarkupPath $markupPath
         */
        $markupPath = $this->getResource();
        $isIndex = $markupPath->isIndexPage();
        if ($isIndex) {
            $parsedValue = $this->getReadStore()->getFromPersistentName(FirstRasterImage::PROPERTY_NAME);
        } else {
            $parsedValue = $this->getReadStore()->getFromPersistentName(self::FEATURED_IMAGE_PARSED);
        }
        if ($parsedValue === null) {
            throw new ExceptionNotFound();
        }
        return WikiPath::createMediaPathFromPath($parsedValue);

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
