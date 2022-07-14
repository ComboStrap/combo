<?php


namespace ComboStrap;


use action_plugin_combo_metatwitter;
use syntax_plugin_combo_follow;

/**
 *
 * Brand button
 *   * basic
 *   * share
 *   * follow
 *
 * @package ComboStrap
 *
 *
 * Share link:
 *   * [Link](https://github.com/mxstbr/sharingbuttons.io/blob/master/js/stores/AppStore.js#L242)
 *   * https://github.com/ellisonleao/sharer.js/blob/main/sharer.js#L72
 * Style:
 *   * [Style](https://github.com/mxstbr/sharingbuttons.io/blob/master/js/stores/AppStore.js#L10)
 *
 * Popup:
 * https://gist.github.com/josephabrahams/9d023596b884e80e37e5
 * https://jonsuh.com/blog/social-share-links/
 * https://stackoverflow.com/questions/11473345/how-to-pop-up-new-window-with-tweet-button
 *
 * Inspired by:
 * http://sharingbuttons.io (Specifically thanks for the data)
 */
class BrandButton
{
    public const WIDGET_BUTTON_VALUE = "button";
    public const WIDGET_LINK_VALUE = "link";
    const WIDGETS = [self::WIDGET_BUTTON_VALUE, self::WIDGET_LINK_VALUE];
    const ICON_SOLID_VALUE = "solid";
    const ICON_SOLID_CIRCLE_VALUE = "solid-circle";
    const ICON_OUTLINE_CIRCLE_VALUE = "outline-circle";
    const ICON_OUTLINE_VALUE = "outline";
    const ICON_TYPES = [self::ICON_SOLID_VALUE, self::ICON_SOLID_CIRCLE_VALUE, self::ICON_OUTLINE_VALUE, self::ICON_OUTLINE_CIRCLE_VALUE, self::ICON_NONE_VALUE];
    const ICON_NONE_VALUE = "none";

    const CANONICAL = "social";


    /**
     * @var string
     */
    private $widget = self::WIDGET_BUTTON_VALUE;
    /**
     * @var mixed|string
     */
    private $iconType = self::ICON_SOLID_VALUE;
    /**
     * The width of the icon
     * @var int|null
     */
    private $width = null;
    /**
     * @var string
     */
    private $type;
    const TYPE_BUTTON_SHARE = "share";
    const TYPE_BUTTON_FOLLOW = "follow";
    const TYPE_BUTTON_BRAND = "brand";
    const TYPE_BUTTONS = [self::TYPE_BUTTON_SHARE, self::TYPE_BUTTON_FOLLOW, self::TYPE_BUTTON_BRAND];


    /**
     * @var string the follow handle
     */
    private $handle;


    /**
     * @var Brand
     */
    private $brand;
    private $primaryColor;
    private $title;
    private $secondaryColor;


    /**
     * @throws ExceptionCompile
     */
    public function __construct(string $brandName, string $typeButton)
    {

        $this->brand = Brand::create($brandName);

        $this->type = strtolower($typeButton);
        if (!in_array($this->type, self::TYPE_BUTTONS)) {
            throw new ExceptionCompile("The button type ($this->type} is unknown.");
        }


    }

    /**
     * Return all combination of widget type and icon type
     * @return array
     */
    public static function getVariants(): array
    {
        $variants = [];
        foreach (self::WIDGETS as $widget) {
            foreach (self::ICON_TYPES as $typeIcon) {
                if ($typeIcon === self::ICON_NONE_VALUE) {
                    continue;
                }
                $variants[] = [\syntax_plugin_combo_brand::ICON_ATTRIBUTE => $typeIcon, TagAttributes::TYPE_KEY => $widget];
            }
        }
        return $variants;
    }

    /**
     * @throws ExceptionCompile
     */
    public static function createBrandButton(string $brand): BrandButton
    {
        return new BrandButton($brand, self::TYPE_BUTTON_BRAND);
    }


    /**
     * @throws ExceptionCompile
     */
    public function setWidget($widget): BrandButton
    {
        /**
         * Widget validation
         */
        $this->widget = $widget;
        $widget = trim(strtolower($widget));
        if (!in_array($widget, self::WIDGETS)) {
            throw new ExceptionCompile("The {$this->type} widget ($widget} is unknown. The possible widgets value are " . implode(",", self::WIDGETS));
        }
        return $this;
    }

    /**
     * @throws ExceptionCompile
     */
    public function setIconType($iconType): BrandButton
    {
        /**
         * Icon Validation
         */
        $this->iconType = $iconType;
        $iconType = trim(strtolower($iconType));
        if (!in_array($iconType, self::ICON_TYPES)) {
            throw new ExceptionCompile("The icon type ($iconType) is unknown. The possible icons value are " . implode(",", self::ICON_TYPES));
        }
        return $this;
    }

    public function setWidth(?int $width): BrandButton
    {
        /**
         * Width
         */
        if ($width === null) {
            return $this;
        }
        $this->width = $width;
        return $this;
    }

    /**
     * @throws ExceptionCompile
     */
    public static function createShareButton(
        string $brandName,
        string $widget = self::WIDGET_BUTTON_VALUE,
        string $icon = self::ICON_SOLID_VALUE,
        ?int   $width = null): BrandButton
    {
        return (new BrandButton($brandName, self::TYPE_BUTTON_SHARE))
            ->setWidget($widget)
            ->setIconType($icon)
            ->setWidth($width);
    }

    /**
     * @throws ExceptionCompile
     */
    public static function createFollowButton(
        string $brandName,
        string $handle = null,
        string $widget = self::WIDGET_BUTTON_VALUE,
        string $icon = self::ICON_SOLID_VALUE,
        ?int   $width = null): BrandButton
    {
        return (new BrandButton($brandName, self::TYPE_BUTTON_FOLLOW))
            ->setHandle($handle)
            ->setWidget($widget)
            ->setIconType($icon)
            ->setWidth($width);
    }

    /**
     *
     *
     * Dictionary has been made with the data found here:
     *   * https://github.com/ellisonleao/sharer.js/blob/main/sharer.js#L72
     *   * and
     * @throws ExceptionBadArgument
     */
    public function getBrandEndpointForPage(PageFragment $requestedPage = null): ?string
    {

        /**
         * Shared/Follow Url template
         */
        $urlTemplate = $this->brand->getWebUrlTemplate($this->type);
        if ($urlTemplate === null) {
            throw new ExceptionBadArgument("The brand ($this) does not support the $this->type button (The $this->type URL is unknown)");
        }
        switch ($this->type) {

            case self::TYPE_BUTTON_SHARE:
                if ($requestedPage === null) {
                    throw new ExceptionBadArgument("The page requested should not be null for a share button when requesting the endpoint uri.");
                }
                $canonicalUrl = $this->getSharedUrlForPage($requestedPage);
                $templateData["url"] = $canonicalUrl;
                $templateData["title"] = $requestedPage->getTitleOrDefault();

                try {
                    $templateData["description"] = $requestedPage->getDescription();
                } catch (ExceptionNotFound $e) {
                    $templateData["description"] = "";
                }

                $templateData["text"] = $this->getTextForPage($requestedPage);

                $via = null;
                if ($this->brand->getName() == \action_plugin_combo_metatwitter::CANONICAL) {
                    $via = substr(action_plugin_combo_metatwitter::COMBO_STRAP_TWITTER_HANDLE, 1);
                }
                if ($via !== null && $via !== "") {
                    $templateData["via"] = $via;
                }
                foreach ($templateData as $key => $value) {
                    $templateData[$key] = urlencode($value);
                }

                return Template::create($urlTemplate)->setProperties($templateData)->render();

            case self::TYPE_BUTTON_FOLLOW:
                if ($this->handle === null) {
                    return $urlTemplate;
                }
                $templateData[syntax_plugin_combo_follow::HANDLE_ATTRIBUTE] = $this->handle;
                return Template::create($urlTemplate)->setProperties($templateData)->render();
            default:
                // The type is mandatory and checked at creation,
                // it should not happen, we don't throw an error
                $message = "Button type ($this->type) is unknown";
                LogUtility::msg($message, LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                return $message;
        }

    }

    public function __toString()
    {
        return $this->brand->__toString();
    }

    public function getLinkTitle(): string
    {
        $title = $this->title;
        if ($title !== null && trim($title) !== "") {
            return $title;
        }
        $title = $this->brand->getTitle($this->iconType);
        if ($title !== null && trim($title) !== "") {
            return $title;
        }
        $name = ucfirst($this->brand->getName());
        switch ($this->type) {
            case self::TYPE_BUTTON_SHARE:
                return "Share this page via $name";
            case self::TYPE_BUTTON_FOLLOW:
                return "Follow us on $name";
            case self::TYPE_BUTTON_BRAND:
                return $name;
            default:
                return "Button type ($this->type) is unknown";
        }
    }

    /**
     * @throws ExceptionCompile
     */
    public
    function getStyle(): string
    {

        /**
         * Default colors
         */
        // make the button/link space square
        $properties["padding"] = "0.375rem 0.375rem";
        switch ($this->widget) {
            case self::WIDGET_LINK_VALUE:
                $properties["vertical-align"] = "middle";
                $properties["display"] = "inline-block";
                $primaryColor = $this->getPrimaryColor();
                if ($primaryColor !== null) {
                    // important because the nav-bar class takes over
                    $properties["color"] = "$primaryColor!important";
                }
                break;
            default:
            case self::WIDGET_BUTTON_VALUE:

                $primary = $this->getPrimaryColor();
                if ($primary === null) {
                    // custom brand default color
                    $primary = ComboStrap::PRIMARY_COLOR;
                }
                $textColor = $this->getTextColor();
                if ($textColor === null || $textColor === "") {
                    $textColor = "#fff";
                }
                $properties["background-color"] = $primary;
                $properties["border-color"] = $primary;
                $properties["color"] = $textColor;
                break;
        }
        switch ($this->iconType) {
            case self::ICON_OUTLINE_VALUE:
                // not for outline circle, it's cut otherwise, don't know why
                $properties["stroke-width"] = "2px";
                break;
        }

        $cssProperties = "\n";
        foreach ($properties as $key => $value) {
            $cssProperties .= "    $key:$value;\n";
        }
        $style = <<<EOF
.{$this->getIdentifierClass()} {{$cssProperties}}
EOF;

        /**
         * Hover Style
         */
        $secondary = $this->getSecondaryColor();
        if ($secondary === null) {
            return $style;
        }
        $hoverProperties = [];
        switch ($this->widget) {
            case self::WIDGET_LINK_VALUE:
                $hoverProperties["color"] = $secondary;
                break;
            default:
            case self::WIDGET_BUTTON_VALUE:
                $textColor = $this->getTextColor();
                $hoverProperties["background-color"] = $secondary;
                $hoverProperties["border-color"] = $secondary;
                $hoverProperties["color"] = $textColor;
                break;
        }
        $hoverCssProperties = "\n";
        foreach ($hoverProperties as $key => $value) {
            $hoverCssProperties .= "    $key:$value;\n";
        }
        $hoverStyle = <<<EOF
.{$this->getIdentifierClass()}:hover, .{$this->getIdentifierClass()}:active {{$hoverCssProperties}}
EOF;

        return <<<EOF
$style
$hoverStyle
EOF;


    }

    public function getBrand(): Brand
    {
        return $this->brand;
    }

    /**
     * The identifier of the {@link BrandButton::getStyle()} script
     * used as script id in the {@link SnippetManager}
     * @return string
     */
    public
    function getStyleScriptIdentifier(): string
    {
        return "{$this->getType()}-{$this->brand->getName()}-{$this->getWidget()}-{$this->getIcon()}";
    }

    /**
     * @return string - the class identifier used in the {@link BrandButton::getStyle()} script
     */
    public
    function getIdentifierClass(): string
    {
        return StyleUtility::addComboStrapSuffix($this->getStyleScriptIdentifier());
    }

    /**
     * @throws ExceptionNotFound
     */
    public
    function getIconAttributes(): array
    {

        $iconName = $this->getResourceIconName();
        $icon = $this->getResourceIconFile();
        if (!FileSystems::exists($icon)) {
            $iconName = $this->brand->getIconName($this->iconType);
            $brandNames = Brand::getAllKnownBrandNames();
            if ($iconName === null && in_array($this->getBrand(), $brandNames)) {
                throw new ExceptionNotFound("No {$this->iconType} icon could be found for the known brand ($this)");
            }
        }
        $attributes = [FetcherSvg::NAME_ATTRIBUTE => $iconName];
        $textColor = $this->getTextColor();
        if ($textColor !== null) {
            $attributes[ColorRgb::COLOR] = $textColor;
        }
        $attributes[Dimension::WIDTH_KEY] = $this->getWidth();

        return $attributes;
    }

    public
    function getTextColor(): ?string
    {

        switch ($this->widget) {
            case self::WIDGET_LINK_VALUE:
                return $this->getPrimaryColor();
            default:
            case self::WIDGET_BUTTON_VALUE:
                return "#fff";
        }

    }

    /**
     * Class added to the link
     * This is just to be boostrap conformance
     */
    public
    function getWidgetClass(): string
    {
        if ($this->widget === self::WIDGET_BUTTON_VALUE) {
            return "btn";
        }
        return "";
    }


    public
    function getWidget(): string
    {
        return $this->widget;
    }

    private
    function getIcon()
    {
        return $this->iconType;
    }

    private
    function getDefaultWidth(): int
    {
        switch ($this->widget) {
            case self::WIDGET_LINK_VALUE:
                return 36;
            case self::WIDGET_BUTTON_VALUE:
            default:
                return 24;
        }
    }

    private
    function getWidth(): ?int
    {
        if ($this->width === null) {
            return $this->getDefaultWidth();
        }
        return $this->width;
    }

    public function hasIcon(): bool
    {
        if ($this->iconType === self::ICON_NONE_VALUE) {
            return false;
        }

        if ($this->brand->getIconName($this->iconType) !== null) {
            return true;
        }

        if (!FileSystems::exists($this->getResourceIconFile())) {
            return false;
        }
        return true;
    }


    /**
     */
    public
    function getTextForPage(PageFragment $requestedPage): string
    {

        try {
            return "{$requestedPage->getTitleOrDefault()} > {$requestedPage->getDescription()}";
        } catch (ExceptionNotFound $e) {
            // no description, may be ?
            return $requestedPage->getTitleOrDefault();
        }

    }

    public
    function getSharedUrlForPage(PageFragment $requestedPage): string
    {
        return $requestedPage->getCanonicalUrl()->toAbsoluteUrlString();
    }

    /**
     * Return the link HTML attributes
     * @throws ExceptionCompile
     */
    public
    function getLinkAttributes(PageFragment $requestedPage = null): TagAttributes
    {


        $logicalTag = $this->type;
        $linkAttributes = TagAttributes::createEmpty($logicalTag);
        $linkAttributes->addComponentAttributeValue(TagAttributes::TYPE_KEY, $logicalTag);
        $linkAttributes->addClassName("{$this->getWidgetClass()} {$this->getIdentifierClass()}");
        $linkTitle = $this->getLinkTitle();
        $linkAttributes->addOutputAttributeValue("title", $linkTitle);
        switch ($this->type) {
            case self::TYPE_BUTTON_SHARE:

                if ($requestedPage === null) {
                    throw new ExceptionCompile("The page requested should not be null for a share button");
                }

                $ariaLabel = "Share on " . ucfirst($this->getBrand());
                $linkAttributes->addOutputAttributeValue("aria-label", $ariaLabel);
                $linkAttributes->addOutputAttributeValue("rel", "nofollow");

                switch ($this->getBrand()) {
                    case "whatsapp":
                        /**
                         * Direct link
                         * For whatsapp, the sharer link is not the good one
                         */
                        $linkAttributes->addOutputAttributeValue("target", "_blank");
                        $linkAttributes->addOutputAttributeValue("href", $this->getBrandEndpointForPage($requestedPage));
                        break;
                    default:
                        /**
                         * Sharer
                         * https://ellisonleao.github.io/sharer.js/
                         */
                        /**
                         * Opens in a popup
                         */
                        $linkAttributes->addOutputAttributeValue("rel", "noopener");

                        PluginUtility::getSnippetManager()->attachJavascriptLibraryForSlot(
                            "sharer",
                            "https://cdn.jsdelivr.net/npm/sharer.js@0.5.0/sharer.min.js",
                            "sha256-AqqY/JJCWPQwZFY/mAhlvxjC5/880Q331aOmargQVLU="
                        );

                        $linkAttributes->addOutputAttributeValue("data-sharer", $this->getBrand()); // the id
                        $linkAttributes->addOutputAttributeValue("data-link", "false");
                        $linkAttributes->addOutputAttributeValue("data-title", $this->getTextForPage($requestedPage));
                        $urlToShare = $this->getSharedUrlForPage($requestedPage);
                        $linkAttributes->addOutputAttributeValue("data-url", $urlToShare);
                        //$linkAttributes->addComponentAttributeValue("href", "#"); // with # we style navigate to the top
                        $linkAttributes->addStyleDeclarationIfNotSet("cursor", "pointer"); // show a pointer (without href, there is none)
                }
                return $linkAttributes;
            case self::TYPE_BUTTON_FOLLOW:

                $ariaLabel = "Follow us on " . ucfirst($this->getBrand());
                $linkAttributes->addOutputAttributeValue("aria-label", $ariaLabel);
                $linkAttributes->addOutputAttributeValue("target", "_blank");
                $linkAttributes->addOutputAttributeValue("rel", "nofollow");
                $href = $this->getBrandEndpointForPage();
                if ($href !== null) {
                    $linkAttributes->addOutputAttributeValue("href", $href);
                }
                return $linkAttributes;
            case self::TYPE_BUTTON_BRAND:
                if ($this->brand->getBrandUrl() !== null) {
                    $linkAttributes->addOutputAttributeValue("href", $this->brand->getBrandUrl());
                }
                return $linkAttributes;
            default:
                return $linkAttributes;

        }


    }


    private
    function getType(): string
    {
        return $this->type;
    }

    public function setHandle(string $handle): BrandButton
    {
        $this->handle = $handle;
        return $this;
    }

    public function setLinkTitle(string $title): BrandButton
    {
        $this->title = $title;
        return $this;
    }

    public function setPrimaryColor(string $color): BrandButton
    {
        $this->primaryColor = $color;
        return $this;
    }

    private function getResourceIconFile(): WikiPath
    {
        $iconName = $this->getResourceIconName();
        $iconPath = str_replace(IconDownloader::COMBO, "", $iconName) . ".svg";
        return WikiPath::createComboResource($iconPath);
    }

    public function setSecondaryColor(string $secondaryColor): BrandButton
    {
        $this->secondaryColor = $secondaryColor;
        return $this;
    }

    private function getResourceIconName(): string
    {
        $comboLibrary = IconDownloader::COMBO;
        return "$comboLibrary:brand:{$this->getBrand()->getName()}:{$this->iconType}";
    }


    private function getPrimaryColor(): ?string
    {
        if ($this->primaryColor !== null) {
            return $this->primaryColor;
        }
        return $this->brand->getPrimaryColor();
    }

    private function getSecondaryColor(): ?string
    {
        if ($this->secondaryColor !== null) {
            return $this->secondaryColor;
        }
        return $this->brand->getSecondaryColor();
    }


}
