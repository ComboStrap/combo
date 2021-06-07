<?php


use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\PluginUtility;

class action_plugin_combo_headingpostprocess extends DokuWiki_Action_Plugin
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
            '_post_process_heading',
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
    function _post_process_heading(&$event, $param)
    {
        /**
         * @var Doku_Handler $handler
         */
        $handler = $event->data;
        $callStack = CallStack::createFromHandler($handler);
        $callStack->moveToStart();

        /**
         * Processing variable about the context
         */
        $actualParsingState = DOKU_LEXER_EXIT; // enter if we have entered a heading, exit otherwise
        $headingEnterCall = null; // the enter call
        while ($actualCall = $callStack->next()) {
            $componentName = $actualCall->getTagName();
            if ($componentName == syntax_plugin_combo_headingatx::TAG) {
                $actualCall->setState(DOKU_LEXER_ENTER);
                $actualParsingState = DOKU_LEXER_ENTER;
                $headingEnterCall = $actualCall;
                if ($handler->getStatus('section')) {
                    $callStack->insertAfter(
                        Call::createNativeCall(
                            'section_close',
                            array(),
                            $actualCall->getPosition()
                        )
                    );
                    $callStack->next();
                }
                continue;
            }
            if ($actualParsingState == DOKU_LEXER_ENTER) {
                // we are in a heading description
                switch ($actualCall->getComponentName()) {
                    case "p_open":
                        $callStack->deleteActualCallAndPrevious();
                        continue 2;
                    case "p_close":
                        /**
                         * Create the exit call
                         * and open the section
                         * Code extracted and adapted from the end of {@link Doku_Handler::header()}
                         */
                        $callStack->insertBefore(
                            Call::createComboCall(
                                syntax_plugin_combo_headingatx::TAG,
                                DOKU_LEXER_EXIT,
                                [
                                    PluginUtility::ATTRIBUTES => $headingEnterCall->getAttributes(),
                                    PluginUtility::STATE => DOKU_LEXER_EXIT
                                ]
                            )
                        );
                        $callStack->insertBefore(
                            Call::createNativeCall(
                                'section_open',
                                array($headingEnterCall->getAttribute(syntax_plugin_combo_headingatx::LEVEL)),
                                $headingEnterCall->getPosition()
                            )
                        );
                        $handler->setStatus('section', true);

                        /**
                         * Delete the p_close
                         * and set the parsing state to not enter
                         */
                        $callStack->deleteActualCallAndPrevious();
                        $actualParsingState = DOKU_LEXER_EXIT;

                        continue 2;
                    default:
                        if ($actualParsingState == DOKU_LEXER_UNMATCHED) {
                            $actualCall->setComboComponent(syntax_plugin_combo_headingatx::TAG);
                        }
                }
            }

        }

        /**
         * If the section was open, close it
         */
        if ($headingEnterCall != null) {
            if ($handler->getStatus('section')) {
                $callStack->insertAfter(
                    Call::createNativeCall(
                        'section_close',
                        array(),
                        $headingEnterCall->getPosition()
                    )
                );
            }
        }

    }
}
