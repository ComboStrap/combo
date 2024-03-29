<?php
/**
 * Action Component
 * Add a button in the edit toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD
 */


require_once(__DIR__ . '/../vendor/autoload.php');


class action_plugin_combo_toolbar extends DokuWiki_Action_Plugin
{

    /**
     * register the event handlers
     *
     * @author Nicolas GERARD
     */
    function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'handle_toolbar', array());
    }

    function handle_toolbar(&$event, $param): bool
    {

        /**
         * Relative path against
         * DOKUBASE/lib/images/toolbar/
         */
        $imageBase = '../../plugins/combo/resources/images';

        $unit = array(
            'type' => 'format',
            'title' => 'Insert an unit test',
            'icon' => $imageBase . '/unit-doc-block.png',
            'open' => '<unit name="default">\n<file lang path>\n</file>\n\t<code lang>',
            'close' => '\n\t</code>\n\t<console>\n\t</console></unit>\n',
            // 'key'    => $unitShortcutKey
        );

        /**
         * This is called from the js.php with a get HTTP
         * There is no knowledge of which page is modified
         */

        $frontmatterInsert = <<<EOF
---json
{
    "name":"short name",
    "canonical":"unique:name",
    "title":"A title for template iteration and search page engine",
    "description":"A description for template iteration and search page engine"
}
---
EOF;
        // https://www.dokuwiki.org/devel:event:toolbar_define
        $frontmatter = array(
            'type' => 'insert',
            'title' => 'Insert a frontmatter',
            'icon' => $imageBase . '/table-of-contents.svg',
            'insert' => $frontmatterInsert,
            'block' => true
        );


        $blockquote = array(
            'type' => 'format',
            'title' => 'blockquote',
            'icon' => $imageBase . '/blockquote-icon.png',
            'open' => '<blockquote>',
            'close' => '</blockquote>',

        );

        $webcode = array(
            'type' => 'format',
            'title' => 'webcode',
            'icon' => $imageBase . '/code-square.svg',
            'open' => '<webcode>\n',
            'close' => '\n</webcode>\n'
            //'key' => $webCodeShortcutKey
        );

        $twitter = array(
            'type' => 'format',
            'title' => 'twitter',
            'icon' => $imageBase . '/twitter.svg',
            'open' => '<blockquote>\n<cite>[[',
            'close' => ']]</cite>\n</blockquote>\n'
            //'key' => $webCodeShortcutKey
        );

        $event->data[] = array(
            'type' => 'picker',
            'title' => "Choose comboStrap component",
            'icon' => $imageBase . '/logo.svg',
            'list' => array($frontmatter, $blockquote, $webcode, $twitter, $unit)
        );




        return true;


    }

}

