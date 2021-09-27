<?php


use ComboStrap\Call;
use ComboStrap\CallStack;

class action_plugin_combo_blockpostprocessing extends DokuWiki_Action_Plugin
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
            '_post_process_block',
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
    function _post_process_block(&$event, $param)
    {
        /**
         * @var Doku_Handler $handler
         */
        $handler = $event->data;
        $callStack = CallStack::createFromHandler($handler);
        $callStack->moveToStart();


        while ($actualCall = $callStack->next()) {
            if ($actualCall->isDisplaySet()) {
                if ($actualCall->getDisplay() === Call::BlOCK_DISPLAY) {


                    /**
                     * Previous call control
                     */
                    $previous = $callStack->previous();
                    if ($previous->isUnMatchedEmptyCall()) {
                        /**
                         * An empty unmatched call will create a block
                         * Delete
                         */
                        $callStack->deleteActualCallAndPrevious();
                        $previous = $callStack->getActualCall();
                    }
                    if ($previous->getTagName() === "p" && $previous->getState() === DOKU_LEXER_ENTER) {
                        $callStack->deleteActualCallAndPrevious();
                    }

                    /**
                     * Go back on the actual call
                     */
                    $callStack->next();

                    /**
                     * Next call Control
                     */
                    $next = $callStack->next();
                    if ($next->getTagName() === "p" && $next->getState() === DOKU_LEXER_EXIT) {
                        $callStack->deleteActualCallAndPrevious();
                    }
                }
            }
        }


    }


}
