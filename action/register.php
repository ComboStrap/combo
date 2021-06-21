<?php
/**
 * Action Component
 * Add a button in the edit toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas GERARD
 */

use ComboStrap\Bootstrap;
use ComboStrap\Site;
use ComboStrap\Snippet;
use ComboStrap\Spacing;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) die();
require_once(__DIR__ . '/../class/PluginUtility.php');


class action_plugin_combo_register extends DokuWiki_Action_Plugin
{

    const FORM_REGISTER_CLASS = "form-register";

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
         * Logo
         */
        $tagAttributes = TagAttributes::createEmpty("register");
        $tagAttributes->addComponentAttributeValue(TagAttributes::WIDTH_KEY, "72");
        $tagAttributes->addComponentAttributeValue(TagAttributes::HEIGHT_KEY, "72");
        $tagAttributes->addComponentAttributeValue(Spacing::SPACING_ATTRIBUTE, "mb-4");
        $logoHtmlImgTag = Site::getLogoImgHtmlTag($tagAttributes);

        $title = "Register";

        $firstColWeight = 4;
        $secondColWeight = 12 - $firstColWeight;


        /**
         * Form
         * https://getbootstrap.com/docs/5.0/forms/layout/#horizontal-form
         */
        $rowClass = "mb-3";
        if (Bootstrap::getBootStrapMajorVersion() == Bootstrap::BootStrapFourMajorVersion) {
            $rowClass = "form-group";
        }
        $formsContent = <<<EOF
<div class="form-register-header">
    $logoHtmlImgTag
    <h1 class="h3 mb-3 font-weight-normal">$title</h1>
</div>
  <div class="row $rowClass">
    <label for="inputUserName" class="col-sm-$firstColWeight col-form-label">Username</label>
    <div class="col-sm-$secondColWeight">
      <input type="text" class="form-control" id="inputUserName" placeholder="Username" tabindex="1" name="login" required="required">
    </div>
  </div>
  <div class="row $rowClass">
    <label for="inputPassword" class="col-sm-$firstColWeight col-form-label">Password</label>
    <div class="col-sm-$secondColWeight">
      <input type="password" class="form-control" id="inputPassword" placeholder="Password" tabindex="2" name="pass" required="required">
    </div>
  </div>
  <div class="row $rowClass">
    <label for="inputPasswordCheck" class="col-sm-$firstColWeight col-form-label">Once Again</label>
    <div class="col-sm-$secondColWeight">
      <input type="password" class="form-control" id="inputPasswordCheck" placeholder="Password" tabindex="3" name="passchk" required="required">
    </div>
  </div>
  <div class="row $rowClass">
    <label for="inputRealName" class="col-sm-$firstColWeight col-form-label">Real Name</label>
    <div class="col-sm-$secondColWeight">
      <input type="text" class="form-control" id="inputRealName" placeholder="Real Name" tabindex="4" name="fullname" required="required">
    </div>
  </div>
  <div class="row $rowClass">
    <label for="inputEmail" class="col-sm-$firstColWeight col-form-label">Email</label>
    <div class="col-sm-$secondColWeight">
      <input type="text" class="form-control" id="inputEmail" placeholder="name@example.com" tabindex="5" name="email" required="required">
    </div>
  </div>
  <button type="submit" class="btn btn-primary" tabindex="6">Sign in</button>
  </div>
EOF;
        //$form->_content = [$formsContent];


        return true;


    }


}

