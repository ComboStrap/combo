<?php


namespace ComboStrap;


use action_plugin_combo_metatwitter;

/**
 * Class SocialChannel
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
class SocialChannel
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

    /**
     * @var array
     */
    private static $channelDictionary;

    /**
     * @var string
     * The name of the channel, we follow
     * the naming of
     * https://github.com/ellisonleao/sharer.js
     */
    private $name;
    /**
     * @var array
     */
    private $channelDict;
    /**
     * @var string
     */
    private $widget = self::WIDGET_BUTTON_VALUE;
    /**
     * @var mixed|string
     */
    private $icon;
    /**
     * The width of the icon
     * @var int|null
     */
    private $width;


    /**
     * SocialChannel constructor.
     * @throws ExceptionCombo
     */
    public function __construct(
        string $channelName,
        string $widget = self::WIDGET_BUTTON_VALUE,
        string $icon = self::ICON_SOLID_VALUE,
        int $width = null)
    {
        $this->name = strtolower($channelName);
        switch ($this->name) {
            case "hn":
                $this->name = "hackernews";
                break;
            case "mail":
                $this->name = "email";
                break;
        }
        /**
         * Get the channels
         */
        if (self::$channelDictionary === null) {
            self::$channelDictionary = Dictionary::getFrom("social-channels");
        }

        /**
         * Get the data for the channel
         */
        $this->channelDict = self::$channelDictionary[$this->name];
        if ($this->channelDict === null) {
            throw new ExceptionCombo("The channel ($this->name} is unknown.");
        }
        /**
         * Widget validation
         */
        $this->widget = $widget;
        $widget = trim(strtolower($widget));
        if (!in_array($widget, self::WIDGETS)) {
            throw new ExceptionCombo("The social widget ($widget} is unknown. The possible widgets value are " . implode(",", self::WIDGETS));
        }
        /**
         * Icon Validation
         */
        $this->icon = $icon;
        $icon = trim(strtolower($icon));
        if (!in_array($icon, self::ICONS)) {
            throw new ExceptionCombo("The social icon ($icon) is unknown. The possible icons value are " . implode(",", self::ICONS));
        }

        /**
         * Width
         */
        $this->width = $width;

    }

    /**
     * @throws ExceptionCombo
     */
    public static function create(
        string $channelName,
        string $widget = self::WIDGET_BUTTON_VALUE,
        string $icon = self::ICON_SOLID_VALUE,
        ?int $width = null): SocialChannel
    {
        return new SocialChannel($channelName, $widget, $icon, $width);
    }

    /**
     * @throws ExceptionCombo
     *
     * Dictionary has been made with the data found here:
     *   * https://github.com/ellisonleao/sharer.js/blob/main/sharer.js#L72
     *   * and
     */
    public function getChannelEndpointForPage(Page $requestedPage): string
    {

        /**
         * Shared Url
         */
        $shareUrlTemplate = $this->channelDict["uri"]["web"];
        if ($shareUrlTemplate === null) {
            throw new ExceptionCombo("The channel ($this) does not have an uri defined for the web");
        }
        $canonicalUrl = $this->getSharedUrlForPage($requestedPage);
        $templateData["url"] = $canonicalUrl;
        $templateData["title"] = $requestedPage->getTitleOrDefault();
        $description = $requestedPage->getDescription();
        if ($description === null) {
            $description = "";
        }
        $templateData["description"] = $description;
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

        return TemplateUtility::renderStringTemplateFromDataArray($shareUrlTemplate, $templateData);

    }

    public function __toString()
    {
        return $this->name;
    }

    public function getLinkTitle(): string
    {
        $name = ucfirst($this->name);
        $title = "Share this page via $name";
        $channelTitle = $this->channelDict["title"];
        if ($channelTitle !== null && $channelTitle !== "") {
            $title = $channelTitle;
        }
        return $title;
    }

    /**
     * @throws ExceptionCombo
     */
    public function getStyle(): string
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
                break;
            default:
            case self::WIDGET_BUTTON_VALUE:

                $primary = $this->channelDict["colors"]["primary"];
                if ($primary === null) {
                    throw new ExceptionCombo("The background color for the social channel ($this) was not found in the data dictionary.");
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
        $secondary = $this->channelDict["colors"]["secondary"];
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

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * The identifier of the {@link SocialChannel::getStyle()} script
     * used as script id in the {@link SnippetManager}
     * @return string
     */
    public function getStyleScriptIdentifier(): string
    {
        return "share-{$this->getName()}-{$this->getWidget()}-{$this->getIcon()}";
    }

    /**
     * @return string - the class identifier used in the {@link SocialChannel::getStyle()} script
     */
    public function getIdentifierClass(): string
    {
        return "{$this->getStyleScriptIdentifier()}-combo";
    }

    /**
     * @throws ExceptionCombo
     */
    public function getIconAttributes(): array
    {

        $comboResourceScheme = DokuPath::COMBO_RESOURCE_SCHEME;
        $iconName = "$comboResourceScheme>share:{$this->getName()}:{$this->icon}.svg";
        $icon = DokuPath::createResource($iconName);
        if (!FileSystems::exists($icon)) {
            $iconName = $this->channelDict["icons"][$this->icon];
            if ($iconName === null) {
                throw new ExceptionCombo("No {$this->icon} icon could be found for the channel ($this)");
            }
        }
        $attributes = [\syntax_plugin_combo_icon::ICON_NAME_ATTRIBUTE => $iconName];
        $textColor = $this->getTextColor();
        if ($textColor !== null) {
            $attributes[ColorUtility::COLOR] = $textColor;
        }
        $attributes[Dimension::WIDTH_KEY] = $this->getWidth();

        return $attributes;
    }

    private function getTextColor()
    {

        switch ($this->widget) {
            case self::WIDGET_LINK_VALUE:
                return $this->channelDict["colors"]["primary"];
            default:
            case self::WIDGET_BUTTON_VALUE:
                return "#fff";
        }

    }

    /**
     * Class added to the link
     * This is just to be boostrap conformance
     */
    public function getWidgetClass(): string
    {
        $class = "link-share";
        if ($this->widget === self::WIDGET_BUTTON_VALUE) {
            $class .= " btn";
        }
        return $class;
    }


    public function getWidget(): string
    {
        return $this->widget;
    }

    private function getIcon()
    {
        return $this->icon;
    }

    private function getDefaultWidth(): int
    {
        switch ($this->widget) {
            case self::WIDGET_LINK_VALUE:
                return 36;
            case self::WIDGET_BUTTON_VALUE:
            default:
                return 24;
        }
    }

    private function getWidth(): ?int
    {
        if ($this->width === null) {
            return $this->getDefaultWidth();
        }
        return $this->width;
    }

    public function hasIcon(): bool
    {
        return $this->icon !== self::ICON_NONE_VALUE;
    }

    public function getTextForPage(Page $requestedPage): ?string
    {
        $text = $requestedPage->getTitleOrDefault();
        $description = $requestedPage->getDescription();
        if ($description !== null) {
            $text .= " > $description";
        }
        return $text;

    }

    public function getSharedUrlForPage(Page $requestedPage): ?string
    {
        return $requestedPage->getCanonicalUrl([], true, DokuwikiUrl::AMPERSAND_URL_ENCODED_FOR_HTML);
    }

    /**
     * Return the link HTML attributes
     * @throws ExceptionCombo
     */
    public function getLinkAttributes(Page $requestedPage): TagAttributes
    {

        $logicalTag = \syntax_plugin_combo_share::TAG;
        $linkAttributes = TagAttributes::createEmpty($logicalTag);
        $linkAttributes->addComponentAttributeValue(TagAttributes::TYPE_KEY, $logicalTag);
        $linkAttributes->addComponentAttributeValue(TagAttributes::CLASS_KEY, "{$this->getWidgetClass()} {$this->getIdentifierClass()}");
        $linkAttributes->addComponentAttributeValue("rel", "noopener");
        $linkTitle = $this->getLinkTitle();
        $linkAttributes->addComponentAttributeValue("title", $linkTitle);
        $ariaLabel = "Share on " . ucfirst($this->getName());
        $linkAttributes->addComponentAttributeValue("aria-label", $ariaLabel);

        switch ($this->getName()) {
            case "whatsapp":
                /**
                 * Direct link
                 * For whatsapp, the sharer link is not the good one
                 */
                $linkAttributes->addComponentAttributeValue("target", "_blank");
                $linkAttributes->addComponentAttributeValue("href", $this->getChannelEndpointForPage($requestedPage));
                break;
            default:
                /**
                 * Sharer
                 * https://ellisonleao.github.io/sharer.js/
                 */
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
                $linkAttributes->addComponentAttributeValue("data-url", $this->getSharedUrlForPage($requestedPage));
                //$linkAttributes->addComponentAttributeValue("href", "#"); // with # we style navigate to the top
                $linkAttributes->addStyleDeclarationIfNotSet("cursor", "pointer"); // show a pointer (without href, there is none)
        }
        return $linkAttributes;

    }


}
