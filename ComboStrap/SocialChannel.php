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
     * SocialChannel constructor.
     * @throws ExceptionCombo
     */
    public function __construct(string $channelName, string $widget = self::WIDGET_BUTTON_VALUE)
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
        $widget = trim(strtolower($widget));
        if (!in_array($widget, self::WIDGETS)) {
            throw new ExceptionCombo("The social widget ($widget} is unknown. The possible widgets value are " . implode(",", self::WIDGETS));
        }
        $this->widget = $widget;
    }

    /**
     * @throws ExceptionCombo
     */
    public static function create(string $channelName, string $widget = self::WIDGET_BUTTON_VALUE): SocialChannel
    {
        return new SocialChannel($channelName, $widget);
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
        $padding = "0.375rem 0.375rem";
        switch ($this->widget) {
            case self::WIDGET_LINK_VALUE:
                // important because there is a conflict with the
                $style = <<<EOF
.{$this->getIdentifierClass()} {
    padding: $padding;
    vertical-align: middle;
    display: inline-block;
}
EOF;
                break;
            default:
            case self::WIDGET_BUTTON_VALUE:

                $background = $this->channelDict["colors"]["background"];
                if ($background === null) {
                    throw new ExceptionCombo("The background color for the social channel ($this) was not found in the data dictionary.");
                }
                $textColor = $this->getTextColor();
                if ($textColor === null || $textColor === "") {
                    $textColor = "#fff";
                }

                $style = <<<EOF
.{$this->getIdentifierClass()} {
    background-color: $background;
    border-color: $background;
    color: $textColor;
    padding: $padding;
}
EOF;
        }

        /**
         * Hover
         */
        $hoverColor = $this->channelDict["colors"]["hover-background"];
        if ($hoverColor === null) {
            return $style;
        }
        switch ($this->widget) {
            case self::WIDGET_LINK_VALUE:
                return <<<EOF
$style

.{$this->getIdentifierClass()}:hover svg, .{$this->getIdentifierClass()}:active svg {
    color: $hoverColor;
}
EOF;
            default:
            case self::WIDGET_BUTTON_VALUE:
                $textColor = $this->getTextColor();
                return <<<EOF
$style

.{$this->getIdentifierClass()}:hover, .{$this->getIdentifierClass()}:active {
    background-color: $hoverColor;
    border-color: $hoverColor;
    color: $textColor;
}
EOF;
        }

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
        return "share-{$this->getName()}-{$this->getWidget()}";
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
    public function getIconAttributes(string $type = "solid"): array
    {

        $iconName = $this->channelDict["icons"][$type];
        if ($iconName === null) {
            throw new ExceptionCombo("The icon type ($type) is undefined for the social channel ({$this->getName()}");
        }
        $attributes = [\syntax_plugin_combo_icon::ICON_NAME_ATTRIBUTE => $iconName];
        $textColor = $this->getTextColor();
        if ($textColor !== null) {
            $attributes[ColorUtility::COLOR] = $textColor;
        }
        return $attributes;
    }

    private function getTextColor()
    {

        switch ($this->widget) {
            case self::WIDGET_LINK_VALUE:
                return $this->channelDict["colors"]["background"];
            default:
            case self::WIDGET_BUTTON_VALUE:
                return $this->channelDict["colors"]["text"];
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


}
