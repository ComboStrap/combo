<?php


use ComboStrap\CacheManager;
use ComboStrap\CallStack;
use ComboStrap\Iso8601Date;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
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


    function handle($match, $state, $pos, Doku_Handler $handler)
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
                if($call!==false) {
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

                        // https://www.php.net/manual/en/function.date.php
                        // To format dates in other languages, you should use the setlocale() and strftime() functions instead of date().

                        $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);

                        /**
                         * Lang ?
                         */
                        $actualLocale = setlocale(LC_ALL, 0);
                        $lang = null;
                        if ($tagAttributes->hasComponentAttribute("lang")) {
                            $lang = $tagAttributes->getComponentAttributeValue("lang");
                            // Set local takes several possible locales value
                            // The lang just works fine but the second argument can be seen in the doc
                            setlocale(LC_TIME, $lang, strtolower($lang) . '_' . strtoupper($lang));
                        }
                        /**
                         * The date (null if none)
                         */
                        $date = $tagAttributes->getComponentAttributeValue(self::DATE_ATTRIBUTE);
                        /**
                         * Date may be wrong
                         */
                        try {
                            $dateTime = Iso8601Date::createFromString($date);
                        } catch (Exception $e) {
                            LogUtility::msg("The string date ($date) is not a valid date",LogUtility::LVL_MSG_ERROR,self::CANONICAL);
                            $renderer->doc .= "<span class=\"text-danger\">String Date value not valid ($date)</span>";
                            return false;
                        }

                        $timeStamp = $dateTime->getDateTime()->getTimestamp();
                        /**
                         * The format
                         */
                        $format = $tagAttributes->getValue(self::FORMAT_ATTRIBUTE, self::DEFAULT_FORMAT);
                        /**
                         * Render
                         */
                        $renderer->doc .= strftime($format, $timeStamp);
                        /**
                         * Restore the locale
                         */
                        if ($lang != null) {
                            setlocale(LC_ALL, $actualLocale);
                        }
                        break;
                }
                break;


        }
        // unsupported $mode
        return false;
    }


}

