<?php

namespace ComboStrap;

use syntax_plugin_combo_link;
use syntax_plugin_combo_menubar;

class
BrandTag
{


    const MARKUP = "brand";
    public const BRAND_TEXT_FOUND_INDICATOR = "brand_text_found";
    public const BRAND_IMAGE_FOUND_INDICATOR = "brand_image_found";
    /**
     * Class needed
     * https://getbootstrap.com/docs/5.1/components/navbar/#image-and-text
     */
    public const BOOTSTRAP_NAV_BAR_IMAGE_AND_TEXT_CLASS = "d-inline-block align-text-top";
    public const URL_ATTRIBUTE = "url";
    public const WIDGET_ATTRIBUTE = "widget";
    public const ICON_ATTRIBUTE = "icon";

    public static function handleSpecialEnter(TagAttributes $tagAttributes, \Doku_Handler $handler): array
    {


        /**
         * Brand text found is updated on exit if there is one
         * Otherwise by default, false
         */
        $returnedArray = [
            self::BRAND_TEXT_FOUND_INDICATOR => false,
            PluginUtility::CONTEXT => "root"
        ];


        /**
         * Context
         */
        $callStack = CallStack::createFromHandler($handler);
        $parent = $callStack->moveToParent();
        if ($parent === false) {
            return $returnedArray;
        }
        $context = $parent->getTagName();
        $returnedArray[PluginUtility::CONTEXT] = $context;

        /**
         * A brand in a menubar is button
         * by default, if there is a value, we return
         * if not we check where the brand is
         */
        $value = $tagAttributes->getComponentAttributeValue(self::WIDGET_ATTRIBUTE);
        if ($value === null) {
            if ($context === syntax_plugin_combo_menubar::TAG) {
                $defaultWidget = BrandButton::WIDGET_LINK_VALUE;
            } else {
                $defaultWidget = BrandButton::WIDGET_BUTTON_VALUE;
            }
            $tagAttributes->addComponentAttributeValue(self::WIDGET_ATTRIBUTE, $defaultWidget);
        }

        return $returnedArray;

    }

    public static function render(TagAttributes $tagAttributes, int $state, array $data): string
    {

        /**
         * Brand Object creation
         */
        $brandName = $tagAttributes->getType();
        try {
            $brandButton = self::createButtonFromAttributes($tagAttributes);
        } catch (ExceptionCompile $e) {
            return LogUtility::wrapInRedForHtml("Error while reading the brand data for the brand ($brandName). Error: {$e->getMessage()}");
        }

        /**
         * Add the Brand button Icon / CSS / Javascript snippet
         */
        try {
            $style = $brandButton->getStyle();
        } catch (ExceptionCompile $e) {
            return LogUtility::wrapInRedForHtml("The style of the {$brandButton->getType()} button ($brandButton) could not be determined. Error: {$e->getMessage()}");
        }
        $snippetId = $brandButton->getStyleScriptIdentifier();
        PluginUtility::getSnippetManager()->attachCssInternalStyleSheet($snippetId, $style);

        /**
         * Link
         */
        try {
            $tagAttributes = self::mixBrandButtonToTagAttributes($tagAttributes, $brandButton);
        } catch (ExceptionCompile $e) {
            return LogUtility::wrapInRedForHtml("Error while getting the link data for the the brand ($brandName). Error: {$e->getMessage()}");
        }
        $context = $data[PluginUtility::CONTEXT];
        if ($context === syntax_plugin_combo_menubar::TAG) {
            $tagAttributes->addOutputAttributeValue("accesskey", "h");
            $tagAttributes->addClassName("navbar-brand");
        }
        // Width does not apply to link (otherwise the link got a max-width of 30)
        $tagAttributes->removeComponentAttributeIfPresent(Dimension::WIDTH_KEY);
        // Widget also
        $tagAttributes->removeComponentAttributeIfPresent(self::WIDGET_ATTRIBUTE);
        $enterAnchor = $tagAttributes
            ->setType(self::MARKUP)
            ->setLogicalTag(syntax_plugin_combo_link::TAG)
            ->toHtmlEnterTag("a");

        $textFound = $data[BrandTag::BRAND_TEXT_FOUND_INDICATOR];

        $htmlOutput = "";
        /**
         * In a link widget, we don't want the logo inside
         * the anchor tag otherwise the underline make a link between the text
         * and the icon and that's ugly
         */
        $logoShouldBeInAnchorElement = !($brandButton->getWidget() === BrandButton::WIDGET_LINK_VALUE && $textFound);
        if ($logoShouldBeInAnchorElement) {
            $htmlOutput .= $enterAnchor;
        }

        /**
         * Logo
         */
        $brandImageFound = $data[BrandTag::BRAND_IMAGE_FOUND_INDICATOR];
        if (!$brandImageFound && $brandButton->hasIcon()) {
            try {
                $iconAttributes = $brandButton->getIconAttributes();
                $iconAttributes = TagAttributes::createFromCallStackArray($iconAttributes);
                if ($textFound && $context === syntax_plugin_combo_menubar::TAG) {
                    $iconAttributes->addClassName(self::BOOTSTRAP_NAV_BAR_IMAGE_AND_TEXT_CLASS);
                }
                $htmlOutput .= Icon::createFromTagAttributes($iconAttributes)
                    ->toHtml();
            } catch (ExceptionCompile $e) {

                if ($brandButton->getBrand()->getName() === Brand::CURRENT_BRAND) {

                    $documentationLink = PluginUtility::getDocumentationHyperLink("logo", "documentation");
                    LogUtility::msg("A svg logo icon is not installed on your website. Check the corresponding $documentationLink.", LogUtility::LVL_MSG_INFO);

                } else {

                    $htmlOutput .= "The brand icon returns an error. Error: {$e->getMessage()}";
                    // we don't return because the link is not closed

                }

            }
        }

        if (!$logoShouldBeInAnchorElement) {
            $htmlOutput .= $enterAnchor;
        }

        /**
         * Special case:
         * Current brand, no logo, no text
         * For current brand
         */
        if (
            $brandButton->getBrand()->getName() === Brand::CURRENT_BRAND
            && !$brandButton->hasIcon()
            && $textFound === false
        ) {
            $htmlOutput .= Site::getName();
        }

        /**
         * End of link
         */
        if ($state === DOKU_LEXER_SPECIAL) {
            $htmlOutput .= "</a>";
        }
        return $htmlOutput;

    }

    /**
     * An utility constructor to be sure that we build the brand button
     * with the same data in the handle and render function
     * @throws ExceptionCompile
     */
    public static function createButtonFromAttributes(TagAttributes $brandAttributes, $type = BrandButton::TYPE_BUTTON_BRAND): BrandButton
    {
        $brandName = $brandAttributes->getValue(TagAttributes::TYPE_KEY, Brand::CURRENT_BRAND);
        $widget = $brandAttributes->getValue(self::WIDGET_ATTRIBUTE, BrandButton::WIDGET_BUTTON_VALUE);
        $icon = $brandAttributes->getValue(self::ICON_ATTRIBUTE, BrandButton::ICON_SOLID_VALUE);

        $brandButton = (new BrandButton($brandName, $type))
            ->setWidget($widget)
            ->setIconType($icon);

        $width = $brandAttributes->getValueAsInteger(Dimension::WIDTH_KEY);
        if ($width !== null) {
            $brandButton->setWidth($width);
        }
        $title = $brandAttributes->getValueAndRemoveIfPresent(syntax_plugin_combo_link::TITLE_ATTRIBUTE);
        if ($title !== null) {
            $brandButton->setLinkTitle($title);
        }
        $color = $brandAttributes->getValueAndRemoveIfPresent(ColorRgb::PRIMARY_VALUE);
        if ($color !== null) {
            $brandButton->setPrimaryColor($color);
        }
        $secondaryColor = $brandAttributes->getValueAndRemoveIfPresent(ColorRgb::SECONDARY_VALUE);
        if ($secondaryColor !== null) {
            $brandButton->setSecondaryColor($secondaryColor);
        }
        $handle = $brandAttributes->getValueAndRemoveIfPresent(Tag\FollowTag::HANDLE_ATTRIBUTE);
        if ($handle !== null) {
            $brandButton->setHandle($handle);
        }
        return $brandButton;
    }

    /**
     * @throws ExceptionCompile
     */
    public static function mixBrandButtonToTagAttributes(TagAttributes $tagAttributes, BrandButton $brandButton): TagAttributes
    {
        $brandLinkAttributes = $brandButton->getHtmlAttributes();
        $urlAttribute = self::URL_ATTRIBUTE;
        $url = $tagAttributes->getValueAndRemoveIfPresent($urlAttribute);
        if ($url !== null) {
            $urlTemplate = Template::create($url);
            $variableDetected = $urlTemplate->getVariablesDetected();
            if (sizeof($variableDetected) === 1 && $variableDetected[0] === "path") {
                try {
                    ExecutionContext::getActualOrCreateFromEnv()
                        ->getExecutingMarkupHandler()
                        ->getOutputCacheDependencies()
                        ->addDependency(MarkupCacheDependencies::REQUESTED_PAGE_DEPENDENCY);
                } catch (ExceptionNotFound $e) {
                    // not a fetcher markup run
                }
                $page = MarkupPath::createFromRequestedPage();
                $relativePath = str_replace(":", "/", $page->getWikiId());
                $url = $urlTemplate
                    ->setProperty("path", $relativePath)
                    ->render();
            }
            $tagAttributes->addOutputAttributeValue("href", $url);
        }
        $brandLinkAttributes->mergeWithCallStackArray($tagAttributes->toCallStackArray());
        // set the type back
        $brandLinkAttributes->setType($tagAttributes->getType());
        return $brandLinkAttributes;
    }



}
