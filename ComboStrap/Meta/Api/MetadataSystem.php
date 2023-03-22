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
use ComboStrap\Meta\Field\PageImagePath;
use ComboStrap\Meta\Field\PageImages;
use ComboStrap\Meta\Field\PageTemplateName;
use ComboStrap\Meta\Field\Region;
use ComboStrap\Meta\Field\SocialCardImage;
use ComboStrap\Meta\Field\TwitterImage;
use ComboStrap\ModificationDate;
use ComboStrap\PageCreationDate;
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

    public const GLOBAL_METADATAS_IDENTIFIER = "metadata-object-array";
    public const METADATAS = [
        Canonical::PROPERTY_NAME,
        PageType::PROPERTY_NAME,
        PageH1::PROPERTY_NAME,
        Aliases::PROPERTY_NAME,
        Region::PROPERTY_NAME,
        Lang::PROPERTY_NAME,
        PageTitle::PROPERTY_NAME,
        PagePublicationDate::PROPERTY_NAME,
        ResourceName::PROPERTY_NAME,
        LdJson::PROPERTY_NAME,
        PageTemplateName::PROPERTY_NAME,
        StartDate::PROPERTY_NAME,
        EndDate::PROPERTY_NAME,
        PageDescription::PROPERTY_NAME,
        DisqusIdentifier::PROPERTY_NAME,
        Slug::PROPERTY_NAME,
        PageKeywords::PROPERTY_NAME,
        CacheExpirationFrequency::PROPERTY_NAME,
        QualityDynamicMonitoringOverwrite::PROPERTY_NAME,
        LowQualityPageOverwrite::PROPERTY_NAME,
        FeaturedSvgImage::PROPERTY_NAME,
        FeaturedRasterImage::PROPERTY_NAME,
        FeaturedIcon::PROPERTY_NAME,
        Lead::PROPERTY_NAME,
        Label::PROPERTY_NAME,
        TwitterImage::PROPERTY_NAME,
        FacebookImage::PROPERTY_NAME
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
        foreach (self::METADATAS as $metadataName) {
            try {
                $metadatas[] = self::getForName($metadataName);
            } catch (ExceptionNotFound $e) {
                $msg = "The metadata $metadataName should be defined";
                if (PluginUtility::isDevOrTest()) {
                    throw new ExceptionRuntimeInternal($msg);
                } else {
                    LogUtility::error($msg);
                }
            }
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
            if($metadata->isMutable()){
                $metas[] = $metadata;
            }
        }
        return $metas;
    }

    public static function getMutableMetadataPropertyName()
    {

    }

    /**
     * @throws ExceptionNotFound
     */
    public static function getForName(string $name): ?Metadata
    {

        $name = strtolower(trim($name));
        /**
         * TODO: the creation could be build automatically if we add a list of metadata class
         */
        switch ($name) {
            case Canonical::getName():
                return new Canonical();
            case PageType::getName():
                return new PageType();
            case PageH1::getName():
                return new PageH1();
            case Aliases::getName():
            case AliasPath::getName():
            case AliasType::getName():
                return new Aliases();
            case PageImages::getName():
            case PageImages::OLD_PROPERTY_NAME:
            case PageImages::getPersistentName():
            case PageImagePath::getName():
            case PageImageUsage::getName():
                return new PageImages();
            case FeaturedRasterImage::getName():
                return new FeaturedRasterImage();
            case FeaturedSvgImage::getName():
                return new FeaturedSvgImage();
            case TwitterImage::getName():
                return new TwitterImage();
            case FacebookImage::getName():
                return new FacebookImage();
            case FeaturedImage::PROPERTY_NAME:
                return new FeaturedImage();
            case SocialCardImage::PROPERTY_NAME:
                return new SocialCardImage();
            case FirstRasterImage::PROPERTY_NAME:
                return new FirstRasterImage();
            case AncestorImage::PROPERTY_NAME:
                return new AncestorImage();
            case FirstSvgImage::PROPERTY_NAME:
                return new FirstSvgImage();
            case FeaturedIcon::PROPERTY_NAME:
                return new FeaturedIcon();
            case FirstImage::PROPERTY_NAME:
                return new FirstImage();
            case Region::OLD_REGION_PROPERTY:
            case Region::getName():
                return new Region();
            case Lang::PROPERTY_NAME:
                return new Lang();
            case PageTitle::TITLE:
                return new PageTitle();
            case PagePublicationDate::OLD_META_KEY:
            case PagePublicationDate::PROPERTY_NAME:
                return new PagePublicationDate();
            case ResourceName::PROPERTY_NAME:
                return new ResourceName();
            case LdJson::OLD_ORGANIZATION_PROPERTY:
            case LdJson::PROPERTY_NAME:
                return new LdJson();
            case PageTemplateName::PROPERTY_NAME:
            case PageTemplateName::PROPERTY_NAME_OLD:
                return new PageTemplateName();
            case StartDate::PROPERTY_NAME:
                return new StartDate();
            case EndDate::PROPERTY_NAME:
                return new EndDate();
            case PageDescription::DESCRIPTION_PROPERTY:
                return new PageDescription();
            case Slug::PROPERTY_NAME:
                return new Slug();
            case PageKeywords::PROPERTY_NAME:
                return new PageKeywords();
            case CacheExpirationFrequency::PROPERTY_NAME:
                return new CacheExpirationFrequency();
            case QualityDynamicMonitoringOverwrite::PROPERTY_NAME:
                return new QualityDynamicMonitoringOverwrite();
            case LowQualityPageOverwrite::PROPERTY_NAME:
                return new LowQualityPageOverwrite();
            case LowQualityCalculatedIndicator::getName():
                return new LowQualityCalculatedIndicator();
            case PageId::PROPERTY_NAME:
                return new PageId();
            case PagePath::PROPERTY_NAME:
                return new PagePath();
            case PageCreationDate::PROPERTY_NAME:
                return new PageCreationDate();
            case ModificationDate::PROPERTY_NAME:
                return new ModificationDate();
            case DokuwikiId::DOKUWIKI_ID_ATTRIBUTE:
                return new DokuwikiId();
            case PageUrlPath::PROPERTY_NAME:
                return new PageUrlPath();
            case Locale::PROPERTY_NAME:
                return new Locale();
            case CacheExpirationDate::PROPERTY_NAME:
                return new CacheExpirationDate();
            case ReplicationDate::getName():
                return new ReplicationDate();
            case PageLevel::PROPERTY_NAME:
                return new PageLevel();
            case DisqusIdentifier::PROPERTY_NAME:
                return new DisqusIdentifier();
            case References::getName():
                return new References();
            case Label::PROPERTY_NAME:
                return new Label();
            case Lead::PROPERTY_NAME:
                return new Lead();
            default:
                $msg = "The metadata ($name) can't be retrieved in the list of metadata. It should be defined";
                LogUtility::msg($msg, LogUtility::LVL_MSG_INFO, Metadata::CANONICAL);
        }
        throw new ExceptionNotFound("No metadata found with the name ($name)");

    }
}
