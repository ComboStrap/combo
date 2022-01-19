<?php


namespace ComboStrap;


use action_plugin_combo_metatwitter;

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


}
