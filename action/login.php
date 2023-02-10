<?php
/**
 * Action Component
 * Add a button in the edit toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD
 */

use ComboStrap\Identity;
use ComboStrap\IdentityFormsHelper;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use dokuwiki\Form\Form;
use dokuwiki\Form\InputElement;
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
    const FIELD_SET_TO_DELETE = ["fieldsetopen", "fieldsetclose"];


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
        print IdentityFormsHelper::getHtmlStyleTag(self::TAG);


        $form->params["class"] = Identity::FORM_IDENTITY_CLASS . " " . self::FORM_LOGIN_CLASS;


        /**
         * Heading
         */
        $newFormContent[] = IdentityFormsHelper::getHeaderHTML($form, self::FORM_LOGIN_CLASS);

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
            $controller->register_hook('FORM_LOGIN_OUTPUT', 'BEFORE', $this, 'handle_login_html', array());
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

    /**
     * https://www.dokuwiki.org/devel:form - documentation
     * @param Form $form
     * @return void
     */
    private static function updateNewFormLogin(Form &$form)
    {
        /**
         * The Login page is an admin page created via buffer
         * We print before the forms
         * to avoid a FOUC
         */
        print IdentityFormsHelper::getHtmlStyleTag(self::TAG);


        $form->addClass(Identity::FORM_IDENTITY_CLASS . " " . self::FORM_LOGIN_CLASS);


        /**
         * Heading
         */
        $headerHTML = IdentityFormsHelper::getHeaderHTML($form, self::FORM_LOGIN_CLASS);
        if ($headerHTML != "") {
            $form->addHTML($headerHTML, 1);
        }


        /**
         * Fieldset and br delete
         */
        IdentityFormsHelper::deleteFieldSetAndBrFromForm($form);

        /**
         * Field
         */
        IdentityFormsHelper::toBootStrapSubmitButton($form);

        /**
         * Name
         */
        $userPosition = $form->findPositionByAttribute("name", "u");
        if ($userPosition === false) {
            LogUtility::msg("Internal error: No user field found");
            return;
        }
        /**
         * @var InputElement $userField
         */
        $userField = $form->getElementAt($userPosition);
        $newUserField = new InputElement($userField->getType(), "u");
        $loginText = $userField->getLabel()->val();
        foreach ($userField->attrs() as $keyAttr => $valueAttr) {
            $newUserField->attr($keyAttr, $valueAttr);
        }
        $newUserField->addClass("form-control");
        $newUserField->attr("placeholder", $loginText);
        $newUserField->attr("required", "required");
        $newUserField->attr("autofocus", "");
        $userFieldId = $userField->attr("id");

        $form->replaceElement($newUserField, $userPosition);

        $form->addHTML("<div class=\"form-floating\">", $userPosition);
        $form->addHTML("<label for=\"$userFieldId\">$loginText</label>", $userPosition + 2);
        $form->addHTML("</div>", $userPosition + 3);


        $pwdPosition = $form->findPositionByAttribute("name", "p");
        if ($pwdPosition === false) {
            LogUtility::msg("Internal error: No password field found");
            return;
        }
        $pwdField = $form->getElementAt($pwdPosition);
        $newPwdField = new InputElement($pwdField->getType(), "p");
        foreach ($pwdField->attrs() as $keyAttr => $valueAttr) {
            $newPwdField->attr($keyAttr, $valueAttr);
        }
        $newPwdField->addClass("form-control");
        $passwordText = $pwdField->getLabel()->val();
        $newPwdField->attr("placeholder", $passwordText);
        $newPwdField->attr("required", "required");
        $pwdFieldId = $newPwdField->attr("id");
        if (empty($pwdFieldId)) {
            $pwdFieldId = "input__password";
            $newPwdField->id($pwdFieldId);
        }
        $form->replaceElement($newPwdField, $pwdPosition);


        $form->addHTML("<div class=\"form-floating\">", $pwdPosition);
        $form->addHTML("<label for=\"$pwdFieldId\">$passwordText</label>", $pwdPosition + 2);
        $form->addHTML("</div>", $pwdPosition + 3);


        $rememberPosition = $form->findPositionByAttribute("name", "r");
        if ($rememberPosition === false) {
            LogUtility::msg("Internal error: No remember field found");
            return;
        }
        $rememberField = $form->getElementAt($rememberPosition);
        $newRememberField = new InputElement($rememberField->getType(), "r");
        foreach ($rememberField->attrs() as $keyAttr => $valueAttr) {
            $newRememberField->attr($keyAttr, $valueAttr);
        }
        $newRememberField->addClass("form-check-input");
        $form->replaceElement($newRememberField, $rememberPosition);

        $remberText = $rememberField->getLabel()->val();
        $remFieldId = $newRememberField->attr("id");

        $form->addHTML("<div class=\"form-check py-2\">", $rememberPosition);
        $form->addHTML("<label for=\"$remFieldId\" class=\"form-check-label\">$remberText</label>", $rememberPosition + 2);
        $form->addHTML("</div>", $rememberPosition + 3);


//        $registerHtml = action_plugin_combo_registration::getRegisterLinkAndParagraph();
//        if (!empty($registerHtml)) {
//            $newFormContent[] = $registerHtml;
//        }
//
//        $resendPwdHtml = action_plugin_combo_resend::getResendPasswordParagraphWithLinkToFormPage();
//        if (!empty($resendPwdHtml)) {
//            $newFormContent[] = $resendPwdHtml;
//        }

    }

}

