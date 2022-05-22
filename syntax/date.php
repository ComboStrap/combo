<?php


use ComboStrap\CacheManager;
use ComboStrap\CallStack;
use ComboStrap\ContextManager;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\Iso8601Date;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PagePath;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\TagAttributes;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Format a date
 */
class syntax_plugin_combo_date extends DokuWiki_Syntax_Plugin
{


    const TAG = "date";


    const CANONICAL = "content:date";

    /**
     * https://www.php.net/manual/en/function.strftime.php
     */
    const DEFAULT_FORMAT = "%A, %d %B %Y";


    const FORMAT_ATTRIBUTE = "format";
    const DATE_ATTRIBUTE = "date";

    /**
     * @param string $date
     * @param string $format
     * @param string|null $locale
     * @return string
     * @throws ExceptionBadSyntax
     */
    public static function formatDateString(string $date, string $format = syntax_plugin_combo_date::DEFAULT_FORMAT, string $locale = null): string
    {
        // https://www.php.net/manual/en/function.date.php
        // To format dates in other languages, you should use the setlocale() and strftime() functions instead of date().
        $localeSeparator = '_';
        if ($locale === null) {
            $path = ContextManager::getOrCreate()->getAttribute(PagePath::PROPERTY_NAME);
            if ($path === null) {
                // should never happen bu yeah
                LogUtility::error("Internal Error: The page content was not set. We were unable to get the page locale. Defaulting to the site locale");
                $locale = Site::getLocale($localeSeparator);
            } else {
                $page = Page::createPageFromQualifiedPath($path);
                $locale = \ComboStrap\Locale::createForPage($page)->getValueOrDefault();
            }
        }
        $actualLocale = setlocale(LC_ALL, 0);
        try {
            if ($locale !== null && trim($locale) !== "") {
                // Set local takes several possible locales value
                // The lang just works fine but the second argument can be seen in the doc
                if (strlen(trim($locale)) === 2) {
                    $derivedLocale = strtolower($locale) . $localeSeparator . strtoupper($locale);
                } else {
                    $derivedLocale = $locale;
                }
                $newLocale = setlocale(LC_TIME, $locale, $derivedLocale);
                if ($newLocale === false) {
                    throw new ExceptionBadSyntax("The language ($locale) is not available as locale on the server. You can't then format the value ($date) in this language.");
                }
            }
            $date = syntax_plugin_combo_variable::replaceVariablesWithValuesFromContext($date);
            $timeStamp = Iso8601Date::createFromString($date)->getDateTime()->getTimestamp();
            $formatted = strftime($format, $timeStamp);
            if ($formatted === false) {
                if ($locale === null) {
                    $locale = "";
                }
                throw new ExceptionBadSyntax("Unable to format the date ($date) with the format ($format) and lang ($locale)");
            }
            return $formatted;
        } finally {
            /**
             * Restore the locale
             */
            setlocale(LC_ALL, $actualLocale);
        }

    }


    function getType(): string
    {
        /**
         * Not protected otherwise
         * a lot of text component such as italic, itext will not work
         */
        return 'formatting';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs (inline)
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
    {
        return 'normal';
    }

    function getAllowedTypes()
    {
        return array();
    }

    function getSort()
    {
        return 201;
    }


    function connectTo($mode)
    {

        $empty = PluginUtility::getEmptyTagPattern(self::TAG);
        $this->Lexer->addSpecialPattern($empty, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
        $pattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }

    function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        switch ($state) {

            case DOKU_LEXER_SPECIAL :
            case DOKU_LEXER_ENTER:
                $attributes = TagAttributes::createFromTagMatch($match);
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes->toCallStackArray()
                );
            case DOKU_LEXER_EXIT:
                $callStack = CallStack::createFromHandler($handler);
                $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
                $call = $callStack->next();
                if ($call !== false) {
                    $date = $call->getCapturedContent();
                    $openingTag->addAttribute(self::DATE_ATTRIBUTE, $date);
                    $callStack->deleteActualCallAndPrevious();
                }
                return array(
                    PluginUtility::STATE => $state
                );

        }
        return array();

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     *
     *
     */
    function render($format, Doku_Renderer $renderer, $data)
    {

        switch ($format) {

            case 'xhtml':
                $state = $data[PluginUtility::STATE];
                switch ($state) {
                    case DOKU_LEXER_SPECIAL:
                    case DOKU_LEXER_ENTER:

                        $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);

                        /**
                         * Locale
                         */
                        $locale = $tagAttributes->getComponentAttributeValue(\ComboStrap\Locale::PROPERTY_NAME);
                        if ($locale === null) {
                            $locale = $tagAttributes->getComponentAttributeValue("lang");
                            if ($locale !== null) {
                                LogUtility::warning("The `lang` attribute of the date component has been deprecated for the `locale` attribute. You should change it.", self::CANONICAL);
                            }
                        }

                        /**
                         * The format
                         */
                        $format = $tagAttributes->getValue(self::FORMAT_ATTRIBUTE, self::DEFAULT_FORMAT);
                        /**
                         * The date
                         */
                        $defaultDateTime = Iso8601Date::createFromNow()->toString();
                        $date = $tagAttributes->getComponentAttributeValue(self::DATE_ATTRIBUTE, $defaultDateTime);
                        try {
                            $renderer->doc .= self::formatDateString($date, $format, $locale);
                        } catch (ExceptionBadSyntax $e) {
                            $message = "Error while formatting a date. Error: {$e->getMessage()}";
                            LogUtility::error($message, self::CANONICAL);
                            $renderer->doc .= LogUtility::wrapInRedForHtml($message);
                            return false;
                        }
                        break;
                }
                break;


        }
        // unsupported $mode
        return false;
    }


}

