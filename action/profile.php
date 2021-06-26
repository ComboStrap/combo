<?php
/**
 * Action Component
 * Add a button in the edit toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD
 */

use ComboStrap\Bootstrap;
use ComboStrap\Identity;
use ComboStrap\LogUtility;
use ComboStrap\Snippet;
use dokuwiki\Menu\Item\Register;

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../class/PluginUtility.php');

/**
 *
 * Register forms depend on the following configuration
 * https://www.dokuwiki.org/config:autopasswd
 * If true, there is no password field
 */
class action_plugin_combo_profile extends DokuWiki_Action_Plugin
{

    const CANONICAL = Identity::CANONICAL;
    const TAG = "profile";
    const FORM_PROFILE_CLASS = "form-" . self::TAG;
    const CONF_ENABLE_PROFILE_FORM = "enableProfileForm";


    function register(Doku_Event_Handler $controller)
    {
        /**
         * To modify the register form and add class
         *
         * Deprecated object passed by the event but still in use
         * https://www.dokuwiki.org/devel:event:html_updateprofileform_output
         */
        $controller->register_hook('HTML_UPDATEPROFILEFORM_OUTPUT', 'BEFORE', $this, 'handle_update_profile', array());

        /**
         * Event using the new object but not found anywhere
         * https://www.dokuwiki.org/devel:event:form_updateprofile_output
         */


    }

    function handle_update_profile(&$event, $param)
    {

        /**
         * The profile page is created via buffer
         * We print before the forms to avoid a FOUC
         */
        print Snippet::createCssSnippet(self::TAG)
            ->getHtmlStyleTag();

        /**
         * @var Doku_Form $form
         */
        $form = &$event->data;
        $class = &$form->params["class"];
        if (isset($class)) {
            $class = $class . " " . self::FORM_PROFILE_CLASS;
        } else {
            $class = self::FORM_PROFILE_CLASS;
        }
        $newFormContent = [];

        /**
         * Header (Logo / Title)
         */
        $newFormContent[] = Identity::getHeaderHTML($form, self::FORM_PROFILE_CLASS);


        /**
         * Form Attributes
         * https://getbootstrap.com/docs/5.0/forms/layout/#horizontal-form
         */
        $rowClass = "row";
        if (Bootstrap::getBootStrapMajorVersion() == Bootstrap::BootStrapFourMajorVersion) {
            $rowClass .= " form-group";
        }
        $firstColWeight = 5;
        $secondColWeight = 12 - $firstColWeight;


        /**
         * Replace the field
         *
         * The password text localized by lang is shared
         * between the password and the password check field
         */
        $passwordText = "Password";
        foreach ($form->_content as $field) {
            if (!is_array($field)) {
                continue;
            }
            $fieldName = $field["name"];
            if ($fieldName == null) {
                // this is not an input field
                switch ($field["type"]) {
                    case "submit":
                        /**
                         * This is important to keep the submit element intact
                         * for forms integration such as captcha
                         * The search the submit button to insert before it
                         */
                        $classes = "btn btn-primary";
                        if (isset($field["class"])) {
                            $field["class"] = $field["class"] . " " . $classes;
                        } else {
                            $field["class"] = $classes;
                        }
                        $field["tabindex"] = "6";
                        $newFormContent[] = $field;
                        break;
                    case "reset":
                        $classes = "btn btn-secondary";
                        if (isset($field["class"])) {
                            $field["class"] = $field["class"] . " " . $classes;
                        } else {
                            $field["class"] = $classes;
                        }
                        $field["tabindex"] = "7";
                        $newFormContent[] = $field;
                        break;
                }
                continue;
            }
            switch ($fieldName) {
                case "login":
                    $loginText = $field["_text"];
                    $loginValue = $field["value"];
                    $loginHTML = <<<EOF
<div class="$rowClass">
    <label for="inputUserName" class="col-sm-$firstColWeight col-form-label">$loginText</label>
    <div class="col-sm-$secondColWeight">
      <input type="text" class="form-control" id="inputUserName" placeholder="Username" name="$fieldName" value="$loginValue" disabled>
    </div>
</div>
EOF;
                    $newFormContent[] = $loginHTML;
                    break;
                case "fullname":
                    $fullNameText = $field["_text"];
                    $fullNameValue = $field["value"];
                    $fullNameHtml = <<<EOF
<div class="$rowClass">
    <label for="inputRealName" class="col-sm-$firstColWeight col-form-label">$fullNameText</label>
    <div class="col-sm-$secondColWeight">
      <input type="text" class="form-control" id="inputRealName" placeholder="$fullNameText" tabindex="1" name="$fieldName" value="$fullNameValue" required="required">
    </div>
</div>
EOF;
                    $newFormContent[] = $fullNameHtml;
                    break;
                case "email":
                    $emailText = $field["_text"];
                    $emailValue = $field["value"];
                    $emailHTML = <<<EOF
<div class="$rowClass">
    <label for="inputEmail" class="col-sm-$firstColWeight col-form-label">$emailText</label>
    <div class="col-sm-$secondColWeight">
      <input type="email" class="form-control" id="inputEmail" placeholder="name@example.com" tabindex="2" name="$fieldName" value="$emailValue" required="required">
    </div>
</div>
EOF;
                    $newFormContent[] = $emailHTML;
                    break;
                case "newpass":
                    $passwordText = $field["_text"];
                    $passwordHtml = <<<EOF
<div class="$rowClass">
    <label for="inputPassword" class="col-sm-$firstColWeight col-form-label">$passwordText</label>
    <div class="col-sm-$secondColWeight">
      <input type="password" class="form-control" id="inputPassword" placeholder="$passwordText" tabindex="3" name="$fieldName">
    </div>
</div>
EOF;
                    $newFormContent[] = $passwordHtml;
                    break;
                case "passchk":
                    $passwordCheckText = $field["_text"];
                    $passwordCheckHtml = <<<EOF
<div class="$rowClass">
    <label for="inputPasswordCheck" class="col-sm-$firstColWeight col-form-label">$passwordCheckText</label>
    <div class="col-sm-$secondColWeight">
      <input type="password" class="form-control" id="inputPasswordCheck" placeholder="$passwordText" tabindex="4" name="$fieldName">
    </div>
</div>
EOF;
                    $newFormContent[] = $passwordCheckHtml;
                    break;
                case "oldpass":
                    $passwordCheckText = $field["_text"];
                    $passwordCheckHtml = <<<EOF
<div class="$rowClass">
    <label for="inputPasswordCheck" class="col-sm-$firstColWeight col-form-label">$passwordCheckText</label>
    <div class="col-sm-$secondColWeight">
      <input type="password" class="form-control" id="inputPasswordCheck" placeholder="$passwordCheckText" tabindex="5" name="$fieldName" required="required">
    </div>
</div>
EOF;
                    $newFormContent[] = $passwordCheckHtml;
                    break;


                default:
                    $tag = self::TAG;
                    LogUtility::msg("The $tag field name ($fieldName) is unknown", LogUtility::LVL_MSG_ERROR, self::CANONICAL);

            }
        }


        /**
         * Update
         */
        $form->_content = $newFormContent;
        return true;


    }


}

