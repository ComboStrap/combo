<?php


namespace ComboStrap;


use action_plugin_combo_metatwitter;
use splitbrain\phpcli\Exception;

/**
 * Class SocialChannel
 * @package ComboStrap
 *
 * Inspired by:
 * http://sharingbuttons.io (Specifically thanks for the data)
 * at:
 *   * [Link](https://github.com/mxstbr/sharingbuttons.io/blob/master/js/stores/AppStore.js#L242)
 *   * [Style](https://github.com/mxstbr/sharingbuttons.io/blob/master/js/stores/AppStore.js#L10)
 *
 * Popup:
 * https://gist.github.com/josephabrahams/9d023596b884e80e37e5
 * https://jonsuh.com/blog/social-share-links/
 * https://stackoverflow.com/questions/11473345/how-to-pop-up-new-window-with-tweet-button
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
    const ICONS = [self::ICON_SOLID_VALUE, self::ICON_SOLID_CIRCLE_VALUE, self::ICON_OUTLINE_VALUE, self::ICON_OUTLINE_CIRCLE_VALUE];

    /**
     * @var array
     */
    private static $channelDictionary;
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
     * SocialChannel constructor.
     * @throws ExceptionCombo
     */
    public function __construct(string $channelName, string $widget = self::WIDGET_BUTTON_VALUE, $icon = self::ICON_SOLID_VALUE)
    {
        $this->name = strtolower($channelName);
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

    }

    /**
     * @throws ExceptionCombo
     */
    public static function create(string $channelName, string $widget = self::WIDGET_BUTTON_VALUE, string $icon = self::ICON_SOLID_VALUE): SocialChannel
    {
        return new SocialChannel($channelName, $widget, $icon);
    }

    /**
     * @throws ExceptionCombo
     */
    public function getUrlForPage(Page $requestedPage): string
    {

        /**
         * Shared Url
         */
        $shareUrlTemplate = $this->channelDict["endpoint"];
        if ($shareUrlTemplate === null) {
            throw new ExceptionCombo("The channel ($this) does not have an endpoint");
        }
        $canonicalUrl = $requestedPage->getCanonicalUrl([], true, DokuwikiUrl::AMPERSAND_URL_ENCODED_FOR_HTML);
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
     */
    public function getIconAttributes(): array
    {

        $iconName = $this->channelDict["icons"][$this->icon];
        if ($iconName === null) {
            $comboResourceScheme = DokuPath::COMBO_RESOURCE_SCHEME;
            $iconName = "$comboResourceScheme>share:{$this->getName()}:{$this->icon}.svg";
        }
        $attributes = [\syntax_plugin_combo_icon::ICON_NAME_ATTRIBUTE => $iconName];
        $textColor = $this->getTextColor();
        if ($textColor !== null) {
            $attributes[ColorUtility::COLOR] = $textColor;
        }
        switch ($this->widget) {
            case self::WIDGET_LINK_VALUE:
                $attributes[Dimension::WIDTH_KEY] = 36;
                break;
            case self::WIDGET_BUTTON_VALUE:
            default:
                $attributes[Dimension::WIDTH_KEY] = 24;

        }
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


}
