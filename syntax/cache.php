<?php


use ComboStrap\CacheExpirationFrequency;
use ComboStrap\ExceptionCombo;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Set the cache of the bar
 * Ie add the possibility to add a time
 * over {@link \dokuwiki\Parsing\ParserMode\Nocache}
 *
 * Depend on the cron dependency
 * https://github.com/dragonmantank/cron-expression
 * @deprecated
 */
class syntax_plugin_combo_cache extends DokuWiki_Syntax_Plugin
{


    const TAG = "cache";

    const PARSING_STATUS = "status";
    const PARSING_STATE_SUCCESSFUL = "successful";
    const PARSING_STATE_UNSUCCESSFUL = "unsuccessful";

    const EXPIRATION_ATTRIBUTE = "expiration";


    function getType()
    {
        return 'protected';
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

        $this->Lexer->addSpecialPattern(PluginUtility::getVoidElementTagPattern(self::TAG), $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {


            case DOKU_LEXER_SPECIAL :

                $attributes = TagAttributes::createFromTagMatch($match);
                $value = $attributes->getValue(self::EXPIRATION_ATTRIBUTE);
                $status = self::PARSING_STATE_SUCCESSFUL;


                $requestPage = Page::createPageFromRequestedPage();

                try {
                    CacheExpirationFrequency::createForPage($requestPage)
                        ->setValue($value)
                        ->sendToWriteStore();
                } catch (ExceptionCombo $e) {
                    $status = self::PARSING_STATE_UNSUCCESSFUL;
                }

                LogUtility::msg("The cache syntax component has been deprecated for the cache frequency metadata", LogUtility::LVL_MSG_INFO, CacheExpirationFrequency::PROPERTY_NAME);

                return array(
                    PluginUtility::STATE => $state,
                    self::PARSING_STATUS => $status,
                    PluginUtility::PAYLOAD => $value
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
    function render($format, Doku_Renderer $renderer, $data): bool
    {

        switch ($format) {

            case 'xhtml':
                if ($data[self::PARSING_STATUS] !== self::PARSING_STATE_SUCCESSFUL) {
                    $cronExpression = $data[PluginUtility::PAYLOAD];
                    LogUtility::msg("The expression ($cronExpression) is not a valid expression", LogUtility::LVL_MSG_ERROR, CacheExpirationFrequency::PROPERTY_NAME);
                }
                break;

            case "metadata":
                if ($data[self::PARSING_STATUS] === self::PARSING_STATE_SUCCESSFUL) {
                    $cronExpression = $data[PluginUtility::PAYLOAD];
                    $requestPage = Page::createPageFromRequestedPage();
                    try {
                        CacheExpirationFrequency::createForPage($requestPage)
                            ->setValue($cronExpression)
                            ->sendToWriteStore();
                    } catch (ExceptionCombo $e) {
                        // should not happen as we test for its validity
                    }

                }


        }
        // unsupported $mode
        return false;
    }


}

