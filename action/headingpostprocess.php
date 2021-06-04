<?php


use ComboStrap\Call;
use ComboStrap\CallStack;

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
     * Delete the combo call for outline heading
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
        $comboHeadingComponents = [syntax_plugin_combo_headingatx::TAG];
        while ($actualCall = $callStack->next()) {
            $componentName = $actualCall->getTagName();
            if (in_array($componentName, $comboHeadingComponents)) {
                $context = $actualCall->getContext();
                if ($context == syntax_plugin_combo_headingutil::TYPE_OUTLINE) {
                    $callStack->deleteActualCallAndPrevious();
                }
            }
            /**
             * {@link \dokuwiki\Parsing\Handler\Block::process()}
             * will add p_open and p_close tag around the {@link syntax_plugin_combo_headingutil}
             * call  whatever the {@link syntax_plugin_combo_headingutil::getPType()}
             * value is (stack does not work)
             */
            if (
                $componentName == syntax_plugin_combo_headingutil::PLUGIN_COMPONENT &&
                $actualCall->getState() == DOKU_LEXER_SPECIAL
            ) {
                $previousCall = $callStack->previous();
                if ($previousCall->getComponentName() == "p_open") {
                    $callStack->deleteActualCallAndPrevious();
                }
                $callStack->next(); // the current
                $nextCall = $callStack->next();
                if ($nextCall->getComponentName() == "p_close") {
                    $callStack->deleteActualCallAndPrevious();
                }
            }

        }

    }
}
