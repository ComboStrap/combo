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

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../class/PluginUtility.php');


class action_plugin_combo_resend extends DokuWiki_Action_Plugin
{

    const CANONICAL = "resend";
    const FORM_RESEND_PWD_CLASS =  "form-" .self::CANONICAL;

    /**
     * @return string
     */
    public static function getResendPasswordParagraphWithLinkToFormPage()
    {
        /**
         * Resend pwd
         */
        $resendPwdHtml = "";
        if (actionOK('resendpwd')) {
            $resendPwLink = (new \dokuwiki\Menu\Item\Resendpwd())->asHtmlLink('', false);
            global $lang;
            $resentText = $lang['pwdforget'];
            $resendPwdHtml = <<<EOF
<p class="resendpwd">$resentText : $resendPwLink</p>
EOF;
        }
        return $resendPwdHtml;
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
         * Event using the new object not found anywhere
         *
         * https://www.dokuwiki.org/devel:event:form_resendpwd_output
         */


    }

    function handle_resendpwd_html(&$event, $param)
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
        $loginCss = Snippet::createCssSnippet(self::CANONICAL);
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
        $form->params["class"] = self::FORM_RESEND_PWD_CLASS;

        /**
         * Heading
         */
        $heading = "Set new password for";
        if (isset($form->_content[0]["_legend"])) {
            $heading = $form->_content[0]["_legend"];
        }

        $submitText = "Set new password";
        $loginText = "Username";
        $loginValue = "";
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
                case "login":
                    $loginText = $field["_text"];
                    $loginValue = $field["value"];
                    break;
                default:
                    LogUtility::msg("The register field name($fieldName) is unknown", LogUtility::LVL_MSG_ERROR, self::CANONICAL);


            }
        }


        /**
         * Logo
         */
        $tagAttributes = TagAttributes::createEmpty(self::CANONICAL);
        $tagAttributes->addComponentAttributeValue(TagAttributes::WIDTH_KEY, "72");
        $tagAttributes->addComponentAttributeValue(TagAttributes::HEIGHT_KEY, "72");
        $tagAttributes->addClassName("logo");
        $logoHtmlImgTag = Site::getLogoImgHtmlTag($tagAttributes);


        /**
         * Register and Login HTML paragraph
         */
        $registerHtml = action_plugin_combo_register::getRegisterLinkAndParagraph();
        $loginHtml = action_plugin_combo_login::getLoginParagraphWithLinkToFormPage();

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
<button class="btn btn-lg btn-primary btn-block" type="submit">$submitText</button>
$loginHtml
$registerHtml
EOF;
        $form->_content = [$formsContent];


        return true;


    }


}

