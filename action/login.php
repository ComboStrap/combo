<?php
/**
 * Action Component
 * Add a button in the edit toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD
 */

use ComboStrap\Resources;
use ComboStrap\Snippet;
use dokuwiki\Form\Form;

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../class/PluginUtility.php');


class action_plugin_combo_login extends DokuWiki_Action_Plugin
{

    function register(Doku_Event_Handler $controller)
    {
        /**
         * To modify the form and add class
         *
         * Deprecated object passed by the event but still in use
         * https://www.dokuwiki.org/devel:event:html_loginform_output
         */
        $controller->register_hook('HTML_LOGINFORM_OUTPUT', 'BEFORE', $this, 'handle_login_html', array());

        /**
         * Event using the new object but only in use in
         * the {@link https://codesearch.dokuwiki.org/xref/dokuwiki/lib/plugins/authad/action.php authad plugin}
         * (ie login against active directory)
         *
         * https://www.dokuwiki.org/devel:event:form_login_output
         */
        // $controller->register_hook('FORM_LOGIN_OUTPUT', 'BEFORE', $this, 'handle_login_html', array());




    }

    function handle_login_html(&$event, $param)
    {

        /**
         * The Login page is created via buffer
         * We print before the forms
         * to avoid a FOUC
         */
        $loginCss = Snippet::createCssSnippet("login");
        $content = $loginCss->getContent();
        $class = $loginCss->getClass();
        $cssHtml =<<<EOF
<style class="$class">
$content
</style>
EOF;
        print $cssHtml;


        /**
         * @var Doku_Form $form
         */
        $form = &$event->data;
        $form->params["class"]="form-signin";


        return true;


    }



}

