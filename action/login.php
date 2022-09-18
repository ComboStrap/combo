<?php
/**
 * Action Component
 * Add a button in the edit toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD
 */

use ComboStrap\Identity;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use dokuwiki\Form\Form;
use dokuwiki\Menu\Item\Login;

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * Class action_plugin_combo_login
 *
 * $conf['rememberme']
 */
class action_plugin_combo_login extends DokuWiki_Action_Plugin
{


    const CANONICAL = Identity::CANONICAL;
    const TAG = "login";
    const FORM_LOGIN_CLASS = "form-" . self::TAG;

    const CONF_ENABLE_LOGIN_FORM = "enableLoginForm";



    /**
     * Update the old form
     * @param Doku_Form $form
     * @return void
     */
    private static function updateDokuFormLogin(Doku_Form &$form)
    {
        /**
         * The Login page is an admin page created via buffer
         * We print before the forms
         * to avoid a FOUC
         */
        print Identity::getHtmlStyleTag(self::TAG);


        $form->params["class"] = Identity::FORM_IDENTITY_CLASS . " " . self::FORM_LOGIN_CLASS;


        /**
         * Heading
         */
        $newFormContent[] = Identity::getHeaderHTML($form, self::FORM_LOGIN_CLASS);

        /**
         * Field
         */
        foreach ($form->_content as $field) {
            if (!is_array($field)) {
                continue;
            }
            $fieldName = $field["name"];
            if ($fieldName == null) {
                // this is not an input field
                if ($field["type"] == "submit") {
                    /**
                     * This is important to keep the submit element intact
                     * for forms integration such as captcha
                     * They search the submit button to insert before it
                     */
                    $classes = "btn btn-primary btn-block";
                    if (isset($field["class"])) {
                        $field["class"] = $field["class"] . " " . $classes;
                    } else {
                        $field["class"] = $classes;
                    }
                    $newFormContent[] = $field;
                }
                continue;
            }
            switch ($fieldName) {
                case "u":
                    $loginText = $field["_text"];
                    $loginValue = $field["value"];
                    $loginHTMLField = <<<EOF
<div class="form-floating">
    <input type="text" id="inputUserName" class="form-control" placeholder="$loginText" required="required" autofocus="" name="u" value="$loginValue">
    <label for="inputUserName">$loginText</label>
</div>
EOF;
                    $newFormContent[] = $loginHTMLField;
                    break;
                case "p":
                    $passwordText = $field["_text"];
                    $passwordFieldHTML = <<<EOF
<div class="form-floating">
    <input type="password" id="inputPassword" class="form-control" placeholder="$passwordText" required="required" name="p">
    <label for="inputPassword">$passwordText</label>
</div>
EOF;
                    $newFormContent[] = $passwordFieldHTML;
                    break;
                case "r":
                    $rememberText = $field["_text"];
                    $rememberValue = $field["value"];
                    $rememberMeHtml = <<<EOF
<div class="checkbox rememberMe">
    <label><input type="checkbox" id="remember__me" name="r" value="$rememberValue"> $rememberText</label>
</div>
EOF;
                    $newFormContent[] = $rememberMeHtml;
                    break;
                default:
                    $tag = self::TAG;
                    LogUtility::msg("The $tag field name ($fieldName) is unknown", LogUtility::LVL_MSG_ERROR, self::CANONICAL);


            }
        }


        $registerHtml = action_plugin_combo_registration::getRegisterLinkAndParagraph();
        if (!empty($registerHtml)) {
            $newFormContent[] = $registerHtml;
        }
        $resendPwdHtml = action_plugin_combo_resend::getResendPasswordParagraphWithLinkToFormPage();
        if (!empty($resendPwdHtml)) {
            $newFormContent[] = $resendPwdHtml;
        }

        /**
         * Set the new in place of the old one
         */
        $form->_content = $newFormContent;
    }


    function register(Doku_Event_Handler $controller)
    {
        /**
         * To modify the form and add class
         *
         * The event HTML_LOGINFORM_OUTPUT is deprecated
         * for FORM_LOGIN_OUTPUT
         *
         * The difference is on the type of object that we got in the event
         */
        if (Site::getConfValue(self::CONF_ENABLE_LOGIN_FORM, 1)) {

            /**
             * Old event: Deprecated object passed by the event but still in use
             * https://www.dokuwiki.org/devel:event:html_loginform_output
             */
            $controller->register_hook('HTML_LOGINFORM_OUTPUT', 'BEFORE', $this, 'handle_login_html', array());

            /**
             * New Event: using the new object but only in use in
             * the {@link https://codesearch.dokuwiki.org/xref/dokuwiki/lib/plugins/authad/action.php authad plugin}
             * (ie login against active directory)
             *
             * https://www.dokuwiki.org/devel:event:form_login_output
             */
            // $controller->register_hook('FORM_LOGIN_OUTPUT', 'BEFORE', $this, 'handle_login_html_new', array());
        }


    }

    function handle_login_html(&$event, $param): void
    {

        $form = &$event->data;
        $class = get_class($form);
        switch ($class) {
            case Doku_Form::class:
                /**
                 * Old one
                 * @var Doku_Form $form
                 */
                self::updateDokuFormLogin($form);
                return;
            case dokuwiki\Form\Form::class;
                /**
                 * New One
                 * @var Form $form
                 */
                self::updateNewFormLogin($form);
                return;
        }


    }



    /**
     * Login
     * @return string
     */
    public static function getLoginParagraphWithLinkToFormPage(): string
    {

        $loginPwLink = (new Login())->asHtmlLink('', false);
        global $lang;
        $loginText = $lang['btn_login'];
        return <<<EOF
<p class="login">$loginText ? : $loginPwLink</p>
EOF;

    }

    private static function updateNewFormLogin(Form &$form)
    {
        // TODO
    }

}

