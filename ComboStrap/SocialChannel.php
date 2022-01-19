<?php


namespace ComboStrap;


use action_plugin_combo_metatwitter;

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
     * SocialChannel constructor.
     * @throws ExceptionCombo
     */
    public function __construct(string $channelName)
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
    }

    /**
     * @throws ExceptionCombo
     */
    public static function create(string $channelName): SocialChannel
    {
        return new SocialChannel($channelName);
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

    public function getStyle(): string
    {
        $background = $this->channelDict["colors"]["background"];
        if ($background === null) {
            return "";
        }
        $textColor = $this->channelDict["colors"]["text"];
        if ($textColor === null || $textColor === "") {
            $textColor = "#fff";
        }
        $style = <<<EOF
.{$this->getClass()} {
    background-color: $background;
    border-color: $background;
    color: $textColor
}
EOF;

        $hoverColor = $this->channelDict["colors"]["hover-background"];
        if ($hoverColor === null) {
            return $style;
        }
        return <<<EOF
$style

.{$this->getClass()}:hover, .{$this->getClass()}:active {
    background-color: $hoverColor;
    border-color: $hoverColor;
    color: $textColor;
}
EOF;

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClass(): string
    {
        return "link-share-{$this->getName()}-combo";
    }

    /**
     * @throws ExceptionCombo
     */
    public function getIconName(string $type = "solid")
    {
        $iconName = $this->channelDict["icons"][$type];
        if ($iconName === null) {
            throw new ExceptionCombo("The icon type ($type) is undefined for the social channle ({$this->getName()}");
        }
        return $iconName;
    }


}
