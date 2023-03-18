<?php

namespace ComboStrap;


use ComboStrap\Tag\BarTag;

/**
 * @deprecated see :container:deprecated
 */
class ContainerTag
{


    public const CONTAINER_ATTRIBUTE = "container";
    public const CONTAINER_VALUES = [ContainerTag::DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE, Breakpoint::MD, Breakpoint::LG, Breakpoint::XL, Breakpoint::XXL, Breakpoint::FLUID];
    public const DEFAULT_LAYOUT_CONTAINER_DEFAULT_VALUE = Breakpoint::SM;
    public const CANONICAL = ContainerTag::TAG;
    /**
     * The value of the default layout container
     * Page Header and Footer have a {@link BarTag} that permits to set the layout container value
     *
     * The page core does not have any It's by default contained for all layout
     * generally applied on the page-core element ie
     * <div id="page-core" data-layout-container="true">
     */
    public const DEFAULT_LAYOUT_CONTAINER_CONF = "defaultLayoutContainer";
    public const TAG = "container";

    public static function getClassName(?string $type): string
    {
        $containerPrefix = "";
        if ($type !== Breakpoint::SM) {
            $containerPrefix = "-$type";
        }
        return "container{$containerPrefix}";
    }

    public static function renderEnterXhtml(TagAttributes $tagAttributes): string
    {
        $type = $tagAttributes->getType();
        $tagAttributes->addClassName(ContainerTag::getClassName($type));
        LogUtility::warning("The container syntax has been deprecated", ":container:deprecated");
        return $tagAttributes->toHtmlEnterTag("div");

    }

    public static function renderExitXhtml(): string
    {
        return '</div>';
    }
}
