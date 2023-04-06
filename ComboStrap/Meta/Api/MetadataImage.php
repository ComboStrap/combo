<?php


namespace ComboStrap\Meta\Api;


use ComboStrap\FeaturedIcon;
use ComboStrap\Meta\Field\FacebookImage;
use ComboStrap\Meta\Field\FeaturedRasterImage;
use ComboStrap\Meta\Field\FeaturedSvgImage;
use ComboStrap\Meta\Field\TwitterImage;
use ComboStrap\MetaManagerForm;

abstract class MetadataImage extends MetadataWikiPath
{

    const PERSISTENT_IMAGE_NAMES = [
        FeaturedIcon::PROPERTY_NAME,
        FeaturedSvgImage::PROPERTY_NAME,
        FeaturedRasterImage::PROPERTY_NAME,
        TwitterImage::PROPERTY_NAME,
        FacebookImage::PROPERTY_NAME
    ];

    static public function getTab(): string
    {
        return MetaManagerForm::TAB_IMAGE_VALUE;
    }

}
