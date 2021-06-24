<?php
/**
 * Action Component
 * Add a button in the edit toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD
 */

use ComboStrap\LogUtility;
use ComboStrap\Site;
use ComboStrap\Snippet;
use ComboStrap\TagAttributes;
use dokuwiki\Menu\Item\Login;
use dokuwiki\Menu\Item\Resendpwd;

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../class/PluginUtility.php');


class action_plugin_combo_login extends DokuWiki_Action_Plugin
{

    const CANONICAL = "login";
    const FORM_LOGIN_CLASS = "form-" . self::CANONICAL;


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
         * Global
         */
        global $conf;
        global $lang;

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
        $form->params["class"] = self::FORM_LOGIN_CLASS;


        /**
         * Heading
         */
        $heading = "Please Sign-in";
        if (isset($form->_content[0]["_legend"])) {
            $heading = $form->_content[0]["_legend"];
        }

        $submitText = "Sign in";
        $loginText = "Username";
        $loginValue = "";
        $passwordText = "Password";
        $rememberText = "Remember me";
        $rememberValue = "1";
        foreach ($form->_content as $field) {
            if (!is_array($field)) {
                continue;
            }
            $fieldName = $field["name"];
            if ($fieldName == null) {
                // this is not an input field
                if ($field["type"] == "submit") {
                    $submitText = $field["value"];
                }
                continue;
            }
            switch ($fieldName) {
                case "u":
                    $loginText = $field["_text"];
                    $loginValue = $field["value"];
                    break;
                case "p":
                    $passwordText = $field["_text"];
                    break;
                case "r":
                    $rememberText = $field["_text"];
                    $rememberValue = $field["value"];
                    break;
                default:
                    LogUtility::msg("The register field name($fieldName) is unknown", LogUtility::LVL_MSG_ERROR, self::CANONICAL);


            }
        }


        /**
         * Logo
         */
        $tagAttributes = TagAttributes::createEmpty("login");
        $tagAttributes->addComponentAttributeValue(TagAttributes::WIDTH_KEY, "72");
        $tagAttributes->addComponentAttributeValue(TagAttributes::HEIGHT_KEY, "72");
        $tagAttributes->addClassName("logo");
        $logoHtmlImgTag = Site::getLogoImgHtmlTag($tagAttributes);


        /**
         * Remember me
         */
        $rememberMeHtml = "";
        if ($conf['rememberme']) {
            $rememberMeHtml = <<<EOF
<div class="checkbox rememberMe">
    <label><input type="checkbox" id="remember__me" name="r" value="$rememberValue"> $rememberText</label>
</div>
EOF;
        }


        $registerHtml = action_plugin_combo_register::getRegisterLinkAndParagraph();
        $resendPwdHtml = action_plugin_combo_resend::getResendPasswordParagraphWithLinkToFormPage();


        /**
         * Based on
         * https://getbootstrap.com/docs/4.0/examples/sign-in/
         */
        $formsContent = <<<EOF
$logoHtmlImgTag
<h1>$heading</h1>
<div class="form-floating">
    <input type="text" id="inputUserName" class="form-control" placeholder="$loginText" required="required" autofocus="" name="u" value="$loginValue">
    <label for="inputUserName">$loginText</label>
</div>
<div class="form-floating">
    <input type="password" id="inputPassword" class="form-control" placeholder="$passwordText" required="required" name="p">
    <label for="inputPassword">$passwordText</label>
</div>
$rememberMeHtml
<button class="btn btn-primary btn-block" type="submit">$submitText</button>
$registerHtml
$resendPwdHtml
EOF;
        $form->_content = [$formsContent];


        return true;


    }


    /**
     * Login
     * @return string
     */
    public static function getLoginParagraphWithLinkToFormPage()
    {

        $loginPwLink = (new Login())->asHtmlLink('', false);
        global $lang;
        $loginText = $lang['btn_login'];
        return <<<EOF
<p class="login">$loginText ? : $loginPwLink</p>
EOF;

    }
}

