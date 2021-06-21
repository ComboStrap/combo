<?php
/**
 * Action Component
 * Add a button in the edit toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD
 */

use ComboStrap\Bootstrap;
use ComboStrap\Resources;
use ComboStrap\Site;
use ComboStrap\Snippet;
use ComboStrap\Spacing;
use ComboStrap\TagAttributes;
use dokuwiki\Form\Form;

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../class/PluginUtility.php');


class action_plugin_combo_login extends DokuWiki_Action_Plugin
{

    const FORM_SIGNIN_CLASS = "form-signin";

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
        $cssHtml = <<<EOF
<style class="$class">
$content
</style>
EOF;
        print $cssHtml;


        /**
         * @var Doku_Form $form
         */
        $form = &$event->data;
        $form->params["class"] = self::FORM_SIGNIN_CLASS;

        /**
         * Logo
         */
        $tagAttributes = TagAttributes::createEmpty("login");
        $tagAttributes->addComponentAttributeValue(TagAttributes::WIDTH_KEY, "72");
        $tagAttributes->addComponentAttributeValue(TagAttributes::HEIGHT_KEY, "72");
        $tagAttributes->addComponentAttributeValue(Spacing::SPACING_ATTRIBUTE, "mb-4");
        $logoHtmlImgTag = Site::getLogoImgHtmlTag($tagAttributes);

        /**
         * Screen reader Only class
         * https://getbootstrap.com/docs/5.0/getting-started/accessibility/#visually-hidden-content
         */
        $screeReaderOnlyClass = "visually-hidden";
        if(Bootstrap::getBootStrapMajorVersion()== Bootstrap::BootStrapFourMajorVersion){
            $screeReaderOnlyClass = "sr-only";
        }

        /**
         * Title
         */
        $formsContent = <<<EOF
$logoHtmlImgTag
<h1 class="h3 mb-3 font-weight-normal">Please sign in</h1>
<label for="inputUserName" class="$screeReaderOnlyClass">Username</label>
<input type="text" id="inputUserName" class="form-control" placeholder="Username" required="" autofocus="" name="u">
<label for="inputPassword" class="$screeReaderOnlyClass">Password</label>
<input type="password" id="inputPassword" class="form-control" placeholder="Password" required="" name="p">
<div class="checkbox mb-3">
    <label><input type="checkbox" id="remember__me" name="r" value="1"> Remember me</label>
</div>
<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
<p class="mt-5 mb-1 text-muted">You don't have an account yet? Just get one: <a href="?do=register" title="Register" rel="nofollow" class="register">Register</a></p>
<p class="mb-3 text-muted">Forgotten your password? Get a new one: <a href="?do=resendpwd" title="Set new password" rel="nofollow" class="resendpwd">Set new password</a></p>
EOF;
        $form->_content = [$formsContent];


        return true;


    }


}

