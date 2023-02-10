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
use dokuwiki\Form\Form;
use dokuwiki\Menu\Item\Resendpwd;

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


class action_plugin_combo_resend extends DokuWiki_Action_Plugin
{

    const CANONICAL = "resend";
    const FORM_RESEND_PWD_CLASS = "form-" . self::CANONICAL;
    const CONF_ENABLE_RESEND_PWD_FORM = "enableResendPwdForm";

    /**
     * @return string
     */
    public static function getResendPasswordParagraphWithLinkToFormPage(): string
    {
        /**
         * Resend pwd
         */
        $resendPwdHtml = "";
        if (actionOK('resendpwd')) {
            $resendPwLink = (new Resendpwd())->asHtmlLink('', false);
            global $lang;
            $resentText = $lang['pwdforget'];
            $resendPwdHtml = <<<EOF
<p class="resendpwd">$resentText : $resendPwLink</p>
EOF;
        }
        return $resendPwdHtml;
    }

    private static function updateNewFormResend(Form &$form)
    {
        // TODO
    }


    private static function updateDokuFormResend(Doku_Form &$form)
    {
        /**
         * The Login page is created via buffer
         * We print before the forms
         * to avoid a FOUC
         */
        print IdentityFormsHelper::getHtmlStyleTag(self::CANONICAL);


        /**
         * @var Doku_Form $form
         */
        $class = &$form->params["class"];
        IdentityFormsHelper::addIdentityClass($class, self::FORM_RESEND_PWD_CLASS);
        $newFormContent = [];


        /**
         * Header (Logo / Title)
         */
        $newFormContent[] = IdentityFormsHelper::getHeaderHTML($form, self::FORM_RESEND_PWD_CLASS);

        /**
         * Form Attributes
         *
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
                     * The search the submit button to insert before it
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
                case "login":
                    $loginText = $field["_text"];
                    $loginValue = $field["value"];
                    $loginHTML = <<<EOF
<div class="form-floating">
    <input type="text" id="inputUserName" class="form-control" placeholder="$loginText" required="required" autofocus="" name="u" value="$loginValue">
    <label for="inputUserName">$loginText</label>
</div>
EOF;
                    $newFormContent[] = $loginHTML;
                    break;
                default:
                    LogUtility::msg("The register field name($fieldName) is unknown", LogUtility::LVL_MSG_ERROR, \ComboStrap\Identity::CANONICAL);


            }
        }


        /**
         * Register and Login HTML paragraph
         */
        $registerHtml = action_plugin_combo_registration::getRegisterLinkAndParagraph();
        if (!empty($registerHtml)) {
            $newFormContent[] = $registerHtml;
        }
        $loginLinkToHtmlForm = action_plugin_combo_login::getLoginParagraphWithLinkToFormPage();
        if (!empty($loginLinkToHtmlForm)) {
            $newFormContent[] = $loginLinkToHtmlForm;
        }

        /**
         * Update
         */
        $form->_content = $newFormContent;

    }


    function register(Doku_Event_Handler $controller)
    {
        /**
         * To modify the form and add class
         *
         * Deprecated object passed by the event but still in use
         * https://www.dokuwiki.org/devel:event:html_resendpwdform_output
         */
        $controller->register_hook('HTML_RESENDPWDFORM_OUTPUT', 'BEFORE', $this, 'handle_resendpwd_html', array());
        /**
         * New Event
         * https://www.dokuwiki.org/devel:event:form_resendpwd_output
         *
         */
        $controller->register_hook('FORM_RESENDPWD_OUTPUT', 'BEFORE', $this, 'handle_resendpwd_html', array());



    }

    function handle_resendpwd_html(&$event, $param)
    {

        $form = &$event->data;
        $class = get_class($form);
        switch ($class) {
            case Doku_Form::class:
                /**
                 * Old one
                 * @var Doku_Form $form
                 */
                self::updateDokuFormResend($form);
                return;
            case dokuwiki\Form\Form::class;
                /**
                 * New One
                 * @var Form $form
                 */
                self::updateNewFormResend($form);
                return;
        }



    }


}

