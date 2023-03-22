<?php

namespace ComboStrap\Meta\Api;

use ComboStrap\CacheExpirationDate;
use ComboStrap\CacheExpirationFrequency;
use ComboStrap\Canonical;
use ComboStrap\DisqusIdentifier;
use ComboStrap\DokuwikiId;
use ComboStrap\EndDate;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionNotFound;
use ComboStrap\ExceptionRuntimeInternal;
use ComboStrap\ExecutionContext;
use ComboStrap\FeaturedIcon;
use ComboStrap\FirstImage;
use ComboStrap\FirstRasterImage;
use ComboStrap\FirstSvgImage;
use ComboStrap\Label;
use ComboStrap\Lang;
use ComboStrap\LdJson;
use ComboStrap\Lead;
use ComboStrap\Locale;
use ComboStrap\LogUtility;
use ComboStrap\LowQualityCalculatedIndicator;
use ComboStrap\LowQualityPageOverwrite;
use ComboStrap\Meta\Field\Aliases;
use ComboStrap\Meta\Field\AliasPath;
use ComboStrap\Meta\Field\AliasType;
use ComboStrap\Meta\Field\AncestorImage;
use ComboStrap\Meta\Field\FacebookImage;
use ComboStrap\Meta\Field\FeaturedImage;
use ComboStrap\Meta\Field\FeaturedRasterImage;
use ComboStrap\Meta\Field\FeaturedSvgImage;
use ComboStrap\Meta\Field\PageH1;
use ComboStrap\Meta\Field\PageImage;
use ComboStrap\Meta\Field\PageImagePath;
use ComboStrap\Meta\Field\PageImages;
use ComboStrap\Meta\Field\PageTemplateName;
use ComboStrap\Meta\Field\Region;
use ComboStrap\Meta\Field\SocialCardImage;
use ComboStrap\Meta\Field\TwitterImage;
use ComboStrap\ModificationDate;
use ComboStrap\CreationDate;
use ComboStrap\PageDescription;
use ComboStrap\PageId;
use ComboStrap\PageImageUsage;
use ComboStrap\PageKeywords;
use ComboStrap\PageLevel;
use ComboStrap\PagePath;
use ComboStrap\PagePublicationDate;
use ComboStrap\PageTitle;
use ComboStrap\PageType;
use ComboStrap\PageUrlPath;
use ComboStrap\PluginUtility;
use ComboStrap\QualityDynamicMonitoringOverwrite;
use ComboStrap\References;
use ComboStrap\ReplicationDate;
use ComboStrap\ResourceName;
use ComboStrap\Slug;
use ComboStrap\StartDate;

class MetadataSystem
{


    /**
     *
     */
    public const METADATAS = [
        Aliases::PROPERTY_NAME => Aliases::class,
        Canonical::PROPERTY_NAME => Canonical::class,
        EndDate::PROPERTY_NAME => EndDate::class,
        PageType::PROPERTY_NAME => PageType::class,
        PageH1::PROPERTY_NAME => PageH1::class,
        Lang::PROPERTY_NAME => Lang::class,
        LdJson::PROPERTY_NAME => LdJson::class,
        LdJson::OLD_ORGANIZATION_PROPERTY => LdJson::class,
        PageTitle::PROPERTY_NAME => PageTitle::class,
        PagePublicationDate::PROPERTY_NAME => PagePublicationDate::class,
        PagePublicationDate::OLD_META_KEY => PagePublicationDate::class,
        Region::PROPERTY_NAME => Region::class,
        ResourceName::PROPERTY_NAME => ResourceName::class,
        StartDate::PROPERTY_NAME => StartDate::class,
        PageDescription::PROPERTY_NAME => PageDescription::class,
        DisqusIdentifier::PROPERTY_NAME => DisqusIdentifier::class,
        Slug::PROPERTY_NAME => Slug::class,
        PageKeywords::PROPERTY_NAME => PageKeywords::class,
        CacheExpirationFrequency::PROPERTY_NAME => CacheExpirationFrequency::class,
        QualityDynamicMonitoringOverwrite::PROPERTY_NAME => QualityDynamicMonitoringOverwrite::class,
        LowQualityPageOverwrite::PROPERTY_NAME => LowQualityPageOverwrite::class,
        FeaturedSvgImage::PROPERTY_NAME => FeaturedSvgImage::class,
        FeaturedRasterImage::PROPERTY_NAME => FeaturedRasterImage::class,
        FeaturedIcon::PROPERTY_NAME => FeaturedIcon::class,
        Lead::PROPERTY_NAME => Lead::class,
        Label::PROPERTY_NAME => Label::class,
        TwitterImage::PROPERTY_NAME => TwitterImage::class,
        FacebookImage::PROPERTY_NAME => FacebookImage::class,
        AliasPath::PROPERTY_NAME => Aliases::class,
        AliasType::PROPERTY_NAME => Aliases::class,
        PageImages::PROPERTY_NAME => PageImages::class,
        PageImages::OLD_PROPERTY_NAME => PageImages::class,
        PageImagePath::PROPERTY_NAME => PageImages::class,
        PageImageUsage::PROPERTY_NAME => PageImages::class,
        SocialCardImage::PROPERTY_NAME => SocialCardImage::class,
        AncestorImage::PROPERTY_NAME => AncestorImage::class,
        FirstImage::PROPERTY_NAME => FirstImage::class,
        Region::OLD_REGION_PROPERTY => Region::class,
        PageTemplateName::PROPERTY_NAME => PageTemplateName::class,
        PageTemplateName::PROPERTY_NAME_OLD => PageTemplateName::class,
        DokuwikiId::DOKUWIKI_ID_ATTRIBUTE => DokuwikiId::class,
        ReplicationDate::PROPERTY_NAME => ReplicationDate::class,
        References::PROPERTY_NAME => References::class,
        LowQualityCalculatedIndicator::PROPERTY_NAME => LowQualityCalculatedIndicator::class,
        PagePath::PROPERTY_NAME => PagePath::class,
        CreationDate::PROPERTY_NAME => CreationDate::class,
        ModificationDate::PROPERTY_NAME => ModificationDate::class,
        PageLevel::PROPERTY_NAME => PageLevel::class,
        PageId::PROPERTY_NAME => PageId::class
    ];


    /**
     * @return Metadata[]
     */
    public static function getMetadataObjects(): array
    {
        /**
         * TODO: create a metadata metadata object and a metadata processing object
         *   We can't cache as we mix for now, in the same object
         *     * the metadata metadata (ie {@link Metadata::isOnForm()}, ...
         *     * and the process object {@link Metadata::setReadStore()}, writestore, value
         */
        $metadatas = [];
        foreach (self::METADATAS as $metadataClass) {
            $metadatas[] = new $metadataClass();
        }
        return $metadatas;

    }

    /**
     * @param object|string $class
     * @param Metadata|null $parent
     * @return Metadata
     * @throws ExceptionBadArgument - if the class is not a metadata class
     */
    public static function toMetadataObject($class, Metadata $parent = null): Metadata
    {
        if (!is_subclass_of($class, Metadata::class)) {
            throw new ExceptionBadArgument("The class ($class) is not a metadata class");
        }
        return new $class($parent);
    }

    /**
     * @return Metadata[]
     */
    public static function getMutableMetadata(): array
    {
        $metas = [];
        foreach (MetadataSystem::getMetadataObjects() as $metadata) {
            if ($metadata::isMutable()) {
                $metas[] = $metadata;
            }
        }
        return $metas;
    }


    /**
     * @throws ExceptionNotFound
     */
    public static function getForName(string $name): Metadata
    {

        $name = strtolower(trim($name));
        $metadataClass = self::METADATAS[$name];
        if ($metadataClass !== null) {
            return new $metadataClass();
        }
        throw new ExceptionNotFound("No metadata found with the name ($name)");

    }
}
