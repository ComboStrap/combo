<?php

use ComboStrap\ExceptionNotFound;
use ComboStrap\Toc;


/**
 *
 * It will overwrite the toc data with our TOC if any
 *
 * This is just cosmetic
 * Because this is almost never used
 *
 * It's needed only if the strap template is not used or in between upgrade
 * in default mode meaning, never, ever but yeah.
 *
 */
class action_plugin_combo_toc extends DokuWiki_Action_Plugin
{


    /**
     *
     * @param Doku_Event_Handler $controller
     */
    public function register(Doku_Event_Handler $controller)
    {

        // https://www.dokuwiki.org/devel:event:tpl_toc_render
        $controller->register_hook('TPL_TOC_RENDER', 'BEFORE', $this, 'handle_toc');


    }

    /**
     * Overwrite the TOC
     * @param Doku_Event $event
     * @param $param
     */
    public function handle_toc(Doku_Event &$event, $param)
    {

        try {
            $toc = Toc::createForRequestedPage()
                ->getValue();
            $event->data = $toc;
        } catch (ExceptionNotFound $e) {
            return;
        }


    }


}

