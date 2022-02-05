<?php


namespace ComboStrap;


use action_plugin_combo_metatwitter;

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
    const ICONS = [self::ICON_SOLID_VALUE, self::ICON_SOLID_CIRCLE_VALUE, self::ICON_OUTLINE_VALUE, self::ICON_OUTLINE_CIRCLE_VALUE, self::ICON_NONE_VALUE];
    const ICON_NONE_VALUE = "none";

    const CANONICAL = "social";
    /**
     * The brand of the current application/website
     */
    public const CURRENT_BRAND = "current";


    /**
     * @var array
     */
    private static $brandDictionary;

    /**
     * @var string
     * The name of the brand,
     * for company, we follow the naming of
     * https://github.com/ellisonleao/sharer.js
     */
    private $name;

    /**
     * @var string
     */
    private $widget = self::WIDGET_BUTTON_VALUE;
    /**
     * @var mixed|string
     */
    private $icon = self::ICON_SOLID_VALUE;
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

    const BRAND_ABBREVIATIONS_MAPPING = [
        "hn" => "hackernews",
        "mail" => "email",
        "wp" => "wikipedia"
    ];


    /**
     * @var string the follow handle
     */
    private $handle;

    /**
     * @var string the endpoint template url (for sharing and following)
     */
    private $webUrlTemplate;
    private $primaryColor;
    private $title;
    private $secondaryColor;
    private $iconName;
    private $brandUrl;


    /**
     * @throws ExceptionCombo
     */
    public function __construct(
        string $brandName,
        string $typeButton)
    {

        $this->name = strtolower($brandName);
        if (isset(self::BRAND_ABBREVIATIONS_MAPPING[$this->name])) {
            $this->name = self::BRAND_ABBREVIATIONS_MAPPING[$this->name];
        }

        $this->type = strtolower($typeButton);
        if (!in_array($this->type, self::TYPE_BUTTONS)) {
            throw new ExceptionCombo("The button type ($this->type} is unknown.");
        }

        /**
         * Get the brands
         */
        self::$brandDictionary = self::getBrandDictionary();


        /**
         * Build the data for the brand
         */
        $brandDict = self::$brandDictionary[$this->name];
        switch ($this->name) {
            case self::CURRENT_BRAND:
                $this->title = Site::getTitle();
                $image = Site::getLogoAsSvgImage();
                if ($image !== null) {
                    $path = $image->getPath();
                    if ($path instanceof DokuPath) {
                        /**
                         * End with svg, not seen as an external icon
                         */
                        $this->iconName = $path->getDokuwikiId();
                    }
                }
                $this->brandUrl = Site::getBaseUrl();
                $primaryColor = Site::getPrimaryColor();
                if ($primaryColor !== null) {
                    $this->primaryColor = $primaryColor->toCssValue();
                }
                $secondaryColor = Site::getSecondaryColor();
                if ($secondaryColor !== null) {
                    // the predicates on the secondary value is to avoid a loop with the the function below
                    $this->secondaryColor = $secondaryColor->toCssValue();
                }
                break;
            default:
                if ($brandDict === null) {
                    throw new ExceptionCombo("The brand ($this->name} is unknown.");
                }
                $this->title = $brandDict[$this->type]["popup"];
                $this->primaryColor = $brandDict["colors"]["primary"];
                $this->secondaryColor = $brandDict["colors"]["secondary"];
                $this->iconName = $brandDict["icons"][$this->icon];
                $this->webUrlTemplate = $brandDict[$this->type]["web"];
                $this->brandUrl = $brandDict["url"];
                break;
        }


    }

    /**
     * @throws ExceptionCombo
     */
    public static function getBrandNames()
    {
        self::$brandDictionary = self::getBrandDictionary();
        $brandsDict = array_keys(self::$brandDictionary);
        $brandsAbbreviations = array_keys(self::BRAND_ABBREVIATIONS_MAPPING);
        return array_merge(
            $brandsDict,
            $brandsAbbreviations,
            [self::CURRENT_BRAND]
        );
    }

    /**
     * @throws ExceptionCombo
     */
    public static function getBrandDictionary()
    {
        if (self::$brandDictionary === null) {

            self::$brandDictionary = Dictionary::getFrom("brands");

        }
        return self::$brandDictionary;
    }

    /**
     * @throws ExceptionCombo
     */
    public static function createBrandButton(string $brandName): BrandButton
    {
        return new BrandButton($brandName, self::TYPE_BUTTON_BRAND);
    }

    /**
     * @throws ExceptionCombo
     */
    public function setWidget($widget): BrandButton
    {
        /**
         * Widget validation
         */
        $this->widget = $widget;
        $widget = trim(strtolower($widget));
        if (!in_array($widget, self::WIDGETS)) {
            throw new ExceptionCombo("The {$this->type} widget ($widget} is unknown. The possible widgets value are " . implode(",", self::WIDGETS));
        }
        return $this;
    }

    /**
     * @throws ExceptionCombo
     */
    public function setIcon($icon): BrandButton
    {
        /**
         * Icon Validation
         */
        $this->icon = $icon;
        $icon = trim(strtolower($icon));
        if (!in_array($icon, self::ICONS)) {
            throw new ExceptionCombo("The social icon ($icon) is unknown. The possible icons value are " . implode(",", self::ICONS));
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
     * @throws ExceptionCombo
     */
    public static function createShareButton(
        string $brandName,
        string $widget = self::WIDGET_BUTTON_VALUE,
        string $icon = self::ICON_SOLID_VALUE,
        ?int $width = null): BrandButton
    {
        return (new BrandButton($brandName, self::TYPE_BUTTON_SHARE))
            ->setWidget($widget)
            ->setIcon($icon)
            ->setWidth($width);
    }

    /**
     * @throws ExceptionCombo
     */
    public static function createFollowButton(
        string $brandName,
        string $handle = null,
        string $widget = self::WIDGET_BUTTON_VALUE,
        string $icon = self::ICON_SOLID_VALUE,
        ?int $width = null): BrandButton
    {
        return (new BrandButton($brandName, self::TYPE_BUTTON_FOLLOW))
            ->setHandle($handle)
            ->setWidget($widget)
            ->setIcon($icon)
            ->setWidth($width);
    }

    /**
     * @throws ExceptionCombo
     *
     * Dictionary has been made with the data found here:
     *   * https://github.com/ellisonleao/sharer.js/blob/main/sharer.js#L72
     *   * and
     */
    public function getBrandEndpointForPage(Page $requestedPage = null): ?string
    {

        /**
         * Shared/Follow Url template
         */
        $urlTemplate = $this->webUrlTemplate;
        if ($urlTemplate === null) {
            throw new ExceptionCombo("The brand ($this) does not support the $this->type button (The $this->type URL is unknown)");
        }
        switch ($this->type) {

            case self::TYPE_BUTTON_SHARE:
                if ($requestedPage === null) {
                    throw new ExceptionCombo("The page requested should not be null for a share button when requesting the endpoint uri.");
                }
                $canonicalUrl = $this->getSharedUrlForPage($requestedPage);
                $templateData["url"] = $canonicalUrl;
                $templateData["title"] = $requestedPage->getTitleOrDefault();
                $description = $requestedPage->getDescription();
                if ($description === null) {
                    $description = "";
                }
                $templateData["description"] = $description;
                $templateData["text"] = $this->getTextForPage($requestedPage);
                $via = null;
                switch ($this->name) {
                    case \action_plugin_combo_metatwitter::CANONICAL:
                        $via = substr(action_plugin_combo_metatwitter::COMBO_STRAP_TWITTER_HANDLE, 1);
                        break;
                }
                if ($via !== null && $via !== "") {
                    $templateData["via"] = $via;
                }
                foreach ($templateData as $key => $value) {
                    $templateData[$key] = urlencode($value);
                }

                return TemplateUtility::renderStringTemplateFromDataArray($urlTemplate, $templateData);

            case self::TYPE_BUTTON_FOLLOW:
                if ($this->handle === null) {
                    return null;
                }
                $templateData["handle"] = $this->handle;
                return TemplateUtility::renderStringTemplateFromDataArray($urlTemplate, $templateData);
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
        if ($this->name === self::CURRENT_BRAND) {
            return $this->name . " (" . Site::getTitle() . ")";
        }
        return $this->name;
    }

    public function getLinkTitle(): string
    {
        $title = $this->title;
        if ($title !== null && trim($title) !== "") {
            return $title;
        }
        $name = ucfirst($this->name);
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
     * @throws ExceptionCombo
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
                if ($this->primaryColor !== null) {
                    // important because the nav-bar class takes over
                    $properties["color"] = "$this->primaryColor!important";
                }
                break;
            default:
            case self::WIDGET_BUTTON_VALUE:

                $primary = $this->primaryColor;
                if ($primary === null) {
                    throw new ExceptionCombo("The primary color for the brand ($this) is not set.");
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
        switch ($this->icon) {
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
.{$this->getIdentifierClass()} { $cssProperties }
EOF;

        /**
         * Hover Style
         */
        $secondary = $this->secondaryColor;
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
.{$this->getIdentifierClass()}:hover, .{$this->getIdentifierClass()}:active { $cssProperties }
EOF;

        return <<<EOF
$style
$hoverStyle
EOF;


    }

    public
    function getName(): string
    {
        return $this->name;
    }

    /**
     * The identifier of the {@link BrandButton::getStyle()} script
     * used as script id in the {@link SnippetManager}
     * @return string
     */
    public
    function getStyleScriptIdentifier(): string
    {
        return "{$this->getType()}-{$this->getName()}-{$this->getWidget()}-{$this->getIcon()}";
    }

    /**
     * @return string - the class identifier used in the {@link BrandButton::getStyle()} script
     */
    public
    function getIdentifierClass(): string
    {
        return "{$this->getStyleScriptIdentifier()}-combo";
    }

    /**
     * @throws ExceptionComboNotFound
     */
    public
    function getIconAttributes(): array
    {

        $comboLibrary = DokuPath::LIBRARY_COMBO;
        $iconName = "$comboLibrary>brand:{$this->getName()}:{$this->icon}.svg";
        $icon = DokuPath::createResource($iconName);
        if (!FileSystems::exists($icon)) {
            $iconName = $this->iconName;
            if ($iconName === null) {
                throw new ExceptionComboNotFound("No {$this->icon} icon could be found for the brand ($this)");
            }
        }
        $attributes = [\syntax_plugin_combo_icon::ICON_NAME_ATTRIBUTE => $iconName];
        $textColor = $this->getTextColor();
        if ($textColor !== null) {
            $attributes[ColorRgb::COLOR] = $textColor;
        }
        $attributes[Dimension::WIDTH_KEY] = $this->getWidth();

        return $attributes;
    }

    public
    function getTextColor()
    {

        switch ($this->widget) {
            case self::WIDGET_LINK_VALUE:
                return $this->primaryColor;
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
        return $this->icon;
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

    public
    function hasIcon(): bool
    {
        return $this->icon !== self::ICON_NONE_VALUE;
    }

    public
    function getTextForPage(Page $requestedPage): ?string
    {
        $text = $requestedPage->getTitleOrDefault();
        $description = $requestedPage->getDescription();
        if ($description !== null) {
            $text .= " > $description";
        }
        return $text;

    }

    public
    function getSharedUrlForPage(Page $requestedPage): ?string
    {
        return $requestedPage->getCanonicalUrl([], true);
    }

    /**
     * Return the link HTML attributes
     * @throws ExceptionCombo
     */
    public
    function getLinkAttributes(Page $requestedPage = null): TagAttributes
    {


        $logicalTag = $this->type;
        $linkAttributes = TagAttributes::createEmpty($logicalTag);
        $linkAttributes->addComponentAttributeValue(TagAttributes::TYPE_KEY, $logicalTag);
        $linkAttributes->addComponentAttributeValue(TagAttributes::CLASS_KEY, "{$this->getWidgetClass()} {$this->getIdentifierClass()}");
        $linkTitle = $this->getLinkTitle();
        $linkAttributes->addComponentAttributeValue("title", $linkTitle);
        switch ($this->type) {
            case self::TYPE_BUTTON_SHARE:

                if ($requestedPage === null) {
                    throw new ExceptionCombo("The page requested should not be null for a share button");
                }

                $ariaLabel = "Share on " . ucfirst($this->getName());
                $linkAttributes->addComponentAttributeValue("aria-label", $ariaLabel);
                $linkAttributes->addComponentAttributeValue("rel", "nofollow");

                switch ($this->getName()) {
                    case "whatsapp":
                        /**
                         * Direct link
                         * For whatsapp, the sharer link is not the good one
                         */
                        $linkAttributes->addComponentAttributeValue("target", "_blank");
                        $linkAttributes->addComponentAttributeValue("href", $this->getBrandEndpointForPage($requestedPage));
                        break;
                    default:
                        /**
                         * Sharer
                         * https://ellisonleao.github.io/sharer.js/
                         */
                        /**
                         * Opens in a popup
                         */
                        $linkAttributes->addComponentAttributeValue("rel", "noopener");

                        PluginUtility::getSnippetManager()->attachTagsForSlot("sharer")
                            ->setTags(
                                array(
                                    "script" =>
                                        [
                                            array(
                                                "src" => "https://cdn.jsdelivr.net/npm/sharer.js@0.5.0/sharer.min.js",
                                                "integrity" => "sha256-AqqY/JJCWPQwZFY/mAhlvxjC5/880Q331aOmargQVLU=",
                                                "crossorigin" => "anonymous"
                                            )
                                        ],

                                ));
                        $linkAttributes->addComponentAttributeValue("data-sharer", $this->getName()); // the id
                        $linkAttributes->addComponentAttributeValue("data-link", "false");
                        $linkAttributes->addComponentAttributeValue("data-title", $this->getTextForPage($requestedPage));
                        $urlToShare = $this->getSharedUrlForPage($requestedPage);
                        $linkAttributes->addComponentAttributeValue("data-url", $urlToShare);
                        //$linkAttributes->addComponentAttributeValue("href", "#"); // with # we style navigate to the top
                        $linkAttributes->addStyleDeclarationIfNotSet("cursor", "pointer"); // show a pointer (without href, there is none)
                }
                return $linkAttributes;
            case self::TYPE_BUTTON_FOLLOW:

                $ariaLabel = "Follow us on " . ucfirst($this->getName());
                $linkAttributes->addComponentAttributeValue("aria-label", $ariaLabel);
                $linkAttributes->addComponentAttributeValue("target", "_blank");
                $linkAttributes->addComponentAttributeValue("rel", "nofollow");
                $href = $this->getBrandEndpointForPage();
                if ($href !== null) {
                    $linkAttributes->addComponentAttributeValue("href", $href);
                }
                return $linkAttributes;
            case self::TYPE_BUTTON_BRAND:
                if ($this->brandUrl !== null) {
                    $linkAttributes->addComponentAttributeValue("href", $this->brandUrl);
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

    private function setHandle(string $handle): BrandButton
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


}
