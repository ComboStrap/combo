<?php


use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\LogUtility;
use ComboStrap\MediaLink;
use ComboStrap\PluginUtility;

/**
 * Because of the automatic processing of p paragraph via {@link \dokuwiki\Parsing\Handler\Block::process()}
 * We get a closing p before and an opening p after
 *
 * We delete them
 *
 */
class action_plugin_combo_tooltippostprocessing extends DokuWiki_Action_Plugin
{


    public function register(\Doku_Event_Handler $controller)
    {
        /**
         * Found in {@link Doku_Handler::finalize()}
         *
         * Doc: https://www.dokuwiki.org/devel:event:parser_handler_done
         */
        $controller->register_hook(
            'PARSER_HANDLER_DONE',
            'AFTER',
            $this,
            '_post_process_tooltip',
            array()
        );

    }


    /**
     * Transform the special heading atx call
     * in an enter and exit heading atx calls
     *
     * Code extracted and adapted from the end of {@link Doku_Handler::header()}
     *
     * @param   $event Doku_Event
     */
    function _post_process_tooltip(&$event, $param)
    {
        /**
         * @var Doku_Handler $handler
         */
        $handler = $event->data;
        $status = $handler->getStatus(syntax_plugin_combo_tooltip::TOOLTIP_FOUND);
        if ($status === true) {

            $callStack = CallStack::createFromHandler($handler);
            $callStack->moveToStart();

            while ($actualCall = $callStack->next()) {

                if ($actualCall->getTagName() == syntax_plugin_combo_tooltip::TAG) {
                    switch ($actualCall->getState()) {
                        case DOKU_LEXER_ENTER:
                            $previous = $callStack->previous();
                            if ($previous !== false) {
                                if ($previous->getTagName() == "p" && $previous->getState() == DOKU_LEXER_EXIT) {
                                    $callStack->deleteActualCallAndPrevious();
                                }
                            }
                            $callStack->next();
                            break;
                        case DOKU_LEXER_EXIT:
                            $next = $callStack->next();
                            if ($next !== false) {
                                if ($next->getTagName() == "p" && $next->getState() == DOKU_LEXER_ENTER) {
                                    $callStack->deleteActualCallAndPrevious();
                                }
                            }
                            break;
                    }
                }

            }

        }
    }


}
