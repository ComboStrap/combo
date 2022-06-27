<?php

use ComboStrap\BacklinkMenuItem;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\Event;
use ComboStrap\ExceptionNotFound;
use ComboStrap\FileSystems;
use ComboStrap\Identity;
use ComboStrap\LogUtility;
use ComboStrap\LinkMarkup;
use ComboStrap\MetadataDokuWikiStore;
use ComboStrap\Mime;
use ComboStrap\PageFragment;
use ComboStrap\PagePath;
use ComboStrap\PluginUtility;
use ComboStrap\Reference;
use ComboStrap\References;
use ComboStrap\Toggle;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 *
 */
class action_plugin_combo_sideslotpostprocessing extends DokuWiki_Action_Plugin
{


    const CANONICAL = "side_slot";


    public function register(Doku_Event_Handler $controller)
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
            '_post_process_side_slot',
            array()
        );


    }

    function _post_process_side_slot(Doku_Event $event, $param)
    {

        try {
            $page = PageFragment::createPageFromGlobalWikiId();
        } catch (ExceptionNotFound $e) {
            if (PluginUtility::isDevOrTest()) {
                LogUtility::error("The global ID was not found. Unable to process the side slot");
            }
            return;
        }
        if (!$page->isSideSlot()) {
            return;
        }
        /**
         * @var Doku_Handler $handler
         */
        $handler = &$event->data;
        $callStack = CallStack::createFromHandler($handler);
        $callStack->moveToStart();

        foreach ($callStack->getChildren() as $child) {

            if ($child->getDisplay() !== Call::BlOCK_DISPLAY || $child->getState() !== DOKU_LEXER_ENTER) {
                /**
                 * Container or not an enter tag (should not but yeah)
                 */
                continue;
            }
            $toggleState = $child->getAttribute(Toggle::TOGGLE_STATE);
            if ($toggleState === null) {
                $child->setAttribute(Toggle::TOGGLE_STATE, "collapsed expanded-md");
            }
        }

    }


}



