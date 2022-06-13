<?php

namespace ComboStrap;

class FirstImage extends MetadataWikiPath
{

    /**
     * Our first image metadata
     * We can't overwrite the {@link \Doku_Renderer_metadata::$firstimage first image}
     * We put it then in directly under the root
     */
    public const FIRST_IMAGE_META_RELATION = "firstimage";

    public static function createForPage(ResourceCombo $resource): FirstImage
    {
        return (new FirstImage())
            ->setResource($resource);
    }

    public function getDescription(): string
    {
        return "The first image of the page";
    }

    public function getLabel(): string
    {
        return "First Image";
    }

    public static function getName(): string
    {
        return self::FIRST_IMAGE_META_RELATION;
    }

    public function getPersistenceType(): string
    {
        return DataType::TEXT_TYPE_VALUE;
    }

    public function getMutable(): bool
    {
        return false;
    }

    public function buildFromReadStore(): FirstImage
    {

        $store = $this->getReadStore();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return $this;
        }

        /**
         * Dokuwiki stores the first image in under relation
         * but as we can't take over the renderer code to enable svg as first image
         * we write it in the root to overcome a conflict
         */
        $firstImageId = $store->getCurrentFromName(FirstImage::FIRST_IMAGE_META_RELATION);

        /**
         * No image set by {@link \syntax_plugin_combo_media::registerFirstImage()}
         * Trying to see if dokuwiki has one
         */
        if($firstImageId===null) {
            $relation = $store->getCurrentFromName('relation');
            if (!isset($relation[FirstImage::FIRST_IMAGE_META_RELATION])) {
                return $this;
            }
            $firstImageId = $relation[FirstImage::FIRST_IMAGE_META_RELATION];
            if (empty($firstImageId)) {
                return $this;
            }
        }

        /**
         * Image Id check
         */
        if (media_isexternal($firstImageId)) {
            // The first image is not a local image
            // Don't set
            return $this;
        }
        $this->buildFromStoreValue($firstImageId);

        return $this;
    }

    /**
     * @throws ExceptionNotFound
     */
    function getImageObject()
    {
        $path = $this->getValue();
        if ($path === null) {
            throw new ExceptionNotFound("No first image for the page ({$this->getResource()}");
        }
        try {
            return FetchImage::createImageFetchFromId($path);
        } catch (ExceptionBadArgument $e) {
            $message = "Internal Error: The image ($path) of the page ({$this->getResource()} is not seen as an image. Error: {$e->getMessage()}";
            // Log to see it in the log and to trigger an error in dev/test
            LogUtility::error($message, self::CANONICAL);
            // Exception not found because this is a state problem that we should not have in production
            throw new ExceptionNotFound("The first image is not a local image");
        }
    }

}
