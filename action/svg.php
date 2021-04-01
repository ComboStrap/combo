<?php


use ComboStrap\SvgFile;
use ComboStrap\SvgImageLink;

if (!defined('DOKU_INC')) exit;
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

/**
 * Class action_plugin_combo_svg
 * Returned an svg optimized version
 */
class action_plugin_combo_svg extends DokuWiki_Action_Plugin
{


    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('FETCH_MEDIA_STATUS', 'BEFORE', $this, 'svg_optimization');
    }

    public function svg_optimization(Doku_Event &$event)
    {

        if ($event->data['ext'] != 'svg') return;
        if ($event->data['status'] >= 400) return; // ACLs and precondition checks

        $svgFile = new SvgFile($event->data['file']);
        $file = $svgFile->getOptimizedSvgFile();
        $event->data['file'] = $file;

    }
}
