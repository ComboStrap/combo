<?php
/**
 * Action Component
 * Add a button in the edit toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD
 */

use ComboStrap\Bootstrap;
use ComboStrap\LogUtility;
use ComboStrap\Site;
use ComboStrap\Snippet;
use ComboStrap\Spacing;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../class/PluginUtility.php');


class action_plugin_combo_register extends DokuWiki_Action_Plugin
{

    const CANONICAL = "register";
    const FORM_REGISTER_CLASS = "form-" . self::CANONICAL;


    function register(Doku_Event_Handler $controller)
    {
        /**
         * To modify the register form and add class
         *
         * Deprecated object passed by the event but still in use
         * https://www.dokuwiki.org/devel:event:html_registerform_output
         */
        $controller->register_hook('HTML_REGISTERFORM_OUTPUT', 'BEFORE', $this, 'handle_register_page', array());

        /**
         * Event using the new object but not yet used
         * https://www.dokuwiki.org/devel:event:form_register_output
         */
        // $controller->register_hook('FORM_REGISTER_OUTPUT', 'BEFORE', $this, 'handle_register', array());


    }

    function handle_register_page(&$event, $param)
    {

        /**
         * The register page is created via buffer
         * We print before the forms
         * to avoid a FOUC
         */
        $loginCss = Snippet::createCssSnippet("register");
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
        $form->params["class"] = self::FORM_REGISTER_CLASS;

        /**
         * Capture the text and value for each fiedl
         */
        $loginText = "Username";
        $loginValue = "";
        $passwordText = "Password";
        $passwordCheckText = "Once Again";
        $fullNameText = "Real name";
        $fullNameValue = "";
        $emailText = "Email";
        $emailValue="";
        $submitText = "Sign-in";
        foreach ($form->_content as $field) {
            $fieldName = $field["name"];
            if($fieldName==null){
                // this is not an input field
                continue;
            }
            switch ($fieldName) {
                case "login":
                    $loginText = $field["_text"];
                    $loginValue = $field["value"];
                    break;
                case "pass":
                    $passwordText = $field["_text"];
                    break;
                case "passchk":
                    $passwordCheckText = $field["_text"];
                    break;
                case "fullname":
                    $fullNameText = $field["_text"];
                    $fullNameValue = $field["value"];
                    break;
                case "email":
                    $emailText = $field["_text"];
                    $emailValue = $field["value"];
                    break;
                default:
                    if ($field["type"] == "submit") {
                        $submitText = $field["value"];
                    } else {
                        LogUtility::msg("The register field name($fieldName) is unknown", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    }

            }
        }

        /**
         * Logo
         */
        $tagAttributes = TagAttributes::createEmpty("register");
        $tagAttributes->addComponentAttributeValue(TagAttributes::WIDTH_KEY, "72");
        $tagAttributes->addComponentAttributeValue(TagAttributes::HEIGHT_KEY, "72");
        $tagAttributes->addClassName("logo");
        $logoHtmlImgTag = Site::getLogoImgHtmlTag($tagAttributes);

        $title = "Register";
        if(isset($form->_content[0]["_legend"])) {
            $title = $form->_content[0]["_legend"];
        }

        $firstColWeight = 5;
        $secondColWeight = 12 - $firstColWeight;


        /**
         * Form
         * https://getbootstrap.com/docs/5.0/forms/layout/#horizontal-form
         */
        $rowClass = "row";
        if (Bootstrap::getBootStrapMajorVersion() == Bootstrap::BootStrapFourMajorVersion) {
            $rowClass .= " form-group";
        }

        /**
         * https://www.dokuwiki.org/config:autopasswd
         */
        $passwordHtml="";
        global $conf;
        if(!$conf['autopasswd']) {
            $passwordHtml = <<<EOF
<div class="$rowClass">
    <label for="inputPassword" class="col-sm-$firstColWeight col-form-label">$passwordText</label>
    <div class="col-sm-$secondColWeight">
      <input type="password" class="form-control" id="inputPassword" placeholder="$passwordText" tabindex="2" name="pass" required="required">
    </div>
</div>
<div class="$rowClass">
    <label for="inputPasswordCheck" class="col-sm-$firstColWeight col-form-label">$passwordCheckText</label>
    <div class="col-sm-$secondColWeight">
      <input type="password" class="form-control" id="inputPasswordCheck" placeholder="$passwordText" tabindex="3" name="passchk" required="required">
    </div>
</div>
EOF;
        }


        $formsContent = <<<EOF
<div class="form-register-header">
    $logoHtmlImgTag
    <h1>$title</h1>
</div>
  <div class="$rowClass">
    <label for="inputUserName" class="col-sm-$firstColWeight col-form-label">$loginText</label>
    <div class="col-sm-$secondColWeight">
      <input type="text" class="form-control" id="inputUserName" placeholder="Username" tabindex="1" name="login" value="$loginValue" required="required">
    </div>
  </div>
  $passwordHtml
  <div class="$rowClass">
    <label for="inputRealName" class="col-sm-$firstColWeight col-form-label">$fullNameText</label>
    <div class="col-sm-$secondColWeight">
      <input type="text" class="form-control" id="inputRealName" placeholder="$fullNameText" tabindex="4" name="fullname" value="$fullNameValue" required="required">
    </div>
  </div>
  <div class="$rowClass">
    <label for="inputEmail" class="col-sm-$firstColWeight col-form-label">$emailText</label>
    <div class="col-sm-$secondColWeight">
      <input type="email" class="form-control" id="inputEmail" placeholder="name@example.com" tabindex="5" name="email" value="$emailValue" required="required">
    </div>
  </div>
  <button type="submit" class="btn btn-primary" tabindex="6">$submitText</button>
EOF;
        $form->_content = [$formsContent];


        return true;


    }


}

