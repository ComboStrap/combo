<?php

use ComboStrap\Bootstrap;
use ComboStrap\Identity;
use ComboStrap\IdentityFormsHelper;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\SiteConfig;
use dokuwiki\Form\Form;
use dokuwiki\Form\InputElement;

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 *
 */
class action_plugin_combo_profile extends DokuWiki_Action_Plugin
{

    const CANONICAL = Identity::CANONICAL;
    const TAG_UPDATE = "profile-update"; // form 1
    const TAG_DELETE = "profile-delete"; // form 2
    const FORM_PROFILE_UPDATE_CLASS = "form-" . self::TAG_UPDATE;
    const FORM_PROFILE_DELETE_CLASS = "form-" . self::TAG_DELETE;
    const CONF_ENABLE_PROFILE_UPDATE_FORM = "enableProfileUpdateForm";
    const CONF_ENABLE_PROFILE_DELETE_FORM = "enableProfileDeleteForm";


    function register(Doku_Event_Handler $controller)
    {
        /**
         * To modify the profile update form and add class
         *
         * Deprecated object passed by the event but still in use
         * https://www.dokuwiki.org/devel:event:html_updateprofileform_output
         *
         * Event using the new object but not found anywhere
         * https://www.dokuwiki.org/devel:event:form_updateprofile_output
         */
        if (SiteConfig::getConfValue(self::CONF_ENABLE_PROFILE_UPDATE_FORM, 1)) {
            $controller->register_hook('HTML_UPDATEPROFILEFORM_OUTPUT', 'BEFORE', $this, 'handle_profile_update', array());
            $controller->register_hook('FORM_UPDATEPROFILE_OUTPUT', 'BEFORE', $this, 'handle_profile_update', array());
        }

        /**
         * To modify the register form and add class
         *
         * Deprecated object passed by the event but still in use
         * https://www.dokuwiki.org/devel:event:html_profiledeleteform_output
         *
         * Event using the new object but not found anywhere
         * https://www.dokuwiki.org/devel:event:form_profiledelete_output
         */
        if (SiteConfig::getConfValue(self::CONF_ENABLE_PROFILE_DELETE_FORM, 1)) {
            $controller->register_hook('HTML_PROFILEDELETEFORM_OUTPUT', 'BEFORE', $this, 'handle_profile_delete', array());
            $controller->register_hook('FORM_PROFILEDELETE_OUTPUT', 'BEFORE', $this, 'handle_profile_delete', array());
        }


    }

    function handle_profile_update(&$event, $param)
    {

        $form = &$event->data;
        $class = get_class($form);
        switch ($class) {
            case "Doku_Form":
                /**
                 * Old one
                 * @var Doku_Form $form
                 */
                self::updateDokuFormProfileUpdate($form);
                return;
            case "dokuwiki\Form\Form";
                /**
                 * New One
                 * @var Form $form
                 */
                self::updateNewFormProfileUpdate($form);
                return;
        }


    }

    public function handle_profile_delete($event, $param)
    {

        $form = &$event->data;
        $class = get_class($form);
        switch ($class) {
            case Doku_Form::class:
                /**
                 * Old one
                 * @var Doku_Form $form
                 */
                self::updateDokuFormProfileDelete($form);
                return;
            case dokuwiki\Form\Form::class;
                /**
                 * New One
                 * @var Form $form
                 */
                self::updateNewFormProfileDelete($form);
                return;
        }


    }

    private static function updateNewFormProfileDelete(Form &$form)
    {
        /**
         * The Login page is an admin page created via buffer
         * We print before the forms
         * to avoid a FOUC
         */
        print IdentityFormsHelper::getHtmlStyleTag(self::TAG_DELETE);


        $deleteFormClassSuffix = self::FORM_PROFILE_DELETE_CLASS;
        $form->addClass(Identity::FORM_IDENTITY_CLASS . " " . $deleteFormClassSuffix);

        /**
         * Heading
         */
        $headerHTML = IdentityFormsHelper::getHeaderHTML($form, $deleteFormClassSuffix, false);
        if ($headerHTML != "") {
            $form->addHTML($headerHTML, 1);
        }

        IdentityFormsHelper::deleteFieldSetAndBrFromForm($form);


        /**
         * Submit button
         */
        $submitButtonPosition = $form->findPositionByAttribute("type", "submit");
        if ($submitButtonPosition === false) {
            LogUtility::msg("Internal error: No submit button found");
            return;
        }
        $form->getElementAt($submitButtonPosition)
            ->addClass("btn")
            ->addClass("btn-primary");

        /**
         * Password Input
         */

        $passwordElementPosition = $form->findPositionByAttribute("type", "password");
        if ($passwordElementPosition === false) {
            LogUtility::msg("Internal error: No password found");
            return;
        }

        IdentityFormsHelper::toBootStrapInputElementAndGetNewLoopingPosition($form, $passwordElementPosition, $deleteFormClassSuffix);


    }

    private static function updateDokuFormProfileDelete(Doku_Form &$form)
    {

        /**
         * The profile page is created via buffer
         * We print before the forms to avoid a FOUC
         */
        print IdentityFormsHelper::getHtmlStyleTag(self::TAG_DELETE);


        $class = &$form->params["class"];
        IdentityFormsHelper::addIdentityClass($class, self::FORM_PROFILE_DELETE_CLASS);
        $newFormContent = [];

        /**
         * Header (Logo / Title)
         */
        $newFormContent[] = IdentityFormsHelper::getHeaderHTML($form, self::FORM_PROFILE_DELETE_CLASS, false);

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
                case "oldpass":
                    $passwordText = $field["_text"];
                    $passwordFieldHTML = <<<EOF
<div>
    <input type="password" class="form-control" placeholder="$passwordText" required="required" name="$fieldName">
</div>
EOF;
                    $newFormContent[] = $passwordFieldHTML;
                    break;
                case "confirm_delete":
                    $confirmText = $field["_text"];
                    $ConfirmValue = $field["value"];
                    $rememberMeHtml = <<<EOF
<div class="checkbox rememberMe">
    <label><input type="checkbox" name="$fieldName" value="$ConfirmValue" required="required"> $confirmText</label>
</div>
EOF;
                    $newFormContent[] = $rememberMeHtml;
                    break;
                default:
                    $tag = self::TAG_DELETE;
                    LogUtility::msg("The $tag field name ($fieldName) is unknown", LogUtility::LVL_MSG_ERROR, self::CANONICAL);


            }
        }
        $form->_content = $newFormContent;

    }

    private static function updateNewFormProfileUpdate(Form &$form)
    {
        /**
         * The Login page is an admin page created via buffer
         * We print before the forms
         * to avoid a FOUC
         */
        print IdentityFormsHelper::getHtmlStyleTag(self::TAG_UPDATE);


        $form->addClass(Identity::FORM_IDENTITY_CLASS . " " . self::FORM_PROFILE_UPDATE_CLASS);


        /**
         * Heading
         */
        $headerHTML = IdentityFormsHelper::getHeaderHTML($form, self::FORM_PROFILE_UPDATE_CLASS);
        if ($headerHTML != "") {
            $form->addHTML($headerHTML, 1);
        }

        IdentityFormsHelper::deleteFieldSetAndBrFromForm($form);

        /**
         * Submit and reset button
         */
        IdentityFormsHelper::toBootStrapSubmitButton($form);
        IdentityFormsHelper::toBootstrapResetButton($form);

        /**
         * Input elements
         */
        IdentityFormsHelper::toBoostrapInputElements($form, self::FORM_PROFILE_UPDATE_CLASS);

    }


    private static function updateDokuFormProfileUpdate(Doku_Form &$form)
    {

        /**
         * The profile page is created via buffer
         * We print before the forms to avoid a FOUC
         */
        print IdentityFormsHelper::getHtmlStyleTag(self::TAG_UPDATE);


        $class = &$form->params["class"];
        IdentityFormsHelper::addIdentityClass($class, self::FORM_PROFILE_UPDATE_CLASS);
        $newFormContent = [];

        /**
         * Header (Logo / Title)
         */
        $newFormContent[] = IdentityFormsHelper::getHeaderHTML($form, self::FORM_PROFILE_UPDATE_CLASS);


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
                    $tag = self::TAG_UPDATE;
                    LogUtility::msg("The $tag field name ($fieldName) is unknown", LogUtility::LVL_MSG_ERROR, self::CANONICAL);

            }
        }


        /**
         * Update
         */
        $form->_content = $newFormContent;

    }

}

