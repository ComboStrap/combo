<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


use Doku_Form;
use TestRequest;

class Identity
{

    const CANONICAL = "identity";
    const CONF_ENABLE_LOGO_ON_IDENTITY_FORMS = "enableLogoOnIdentityForms";
    const JS_NAVIGATION_ANONYMOUS_VALUE = "anonymous";
    const JS_NAVIGATION_SIGNED_VALUE = "signed";
    /**
     * A javascript indicator
     * to know if the user is logged in or not
     * (ie public or not)
     */
    const JS_NAVIGATION_INDICATOR = "navigation";

    const FORM_IDENTITY_CLASS = "form-identity";

    /**
     * Is logged in
     * @return boolean
     */
    public static function isLoggedIn()
    {
        $loggedIn = false;
        global $INPUT;
        if ($INPUT->server->has('REMOTE_USER')) {
            $loggedIn = true;
        }
        return $loggedIn;
    }

    /**
     * @param TestRequest $request
     * @param string $user
     */
    public static function becomeSuperUser(&$request = null, $user = 'admin')
    {
        global $conf;
        $conf['useacl'] = 1;
        $conf['superuser'] = $user;
        $conf['remoteuser'] = $user;

        if ($request != null) {
            $request->setServer('REMOTE_USER', $user);
        } else {
            global $INPUT;
            $INPUT->server->set('REMOTE_USER', $user);
            // same as $_SERVER['REMOTE_USER'] = $user;
        }

        // $_SERVER[] = $user;
        // global $USERINFO;
        // $USERINFO['grps'] = array('admin', 'user');

        // global $INFO;
        // $INFO['ismanager'] = true;

    }

    /**
     * @param $request
     * @param string $user - the user to login
     */
    public static function logIn(&$request, $user = 'defaultUser')
    {

        $request->setServer('REMOTE_USER', $user);

        /**
         * The {@link getSecurityToken()} needs it
         */
        global $INPUT;
        $INPUT->server->set('REMOTE_USER', $user);

    }

    /**
     * @return bool if edit auth
     */
    public static function isWriter($pageId = null): bool
    {
        if ($pageId == null) {
            $pageId = Page::createPageFromGlobalDokuwikiId();
        }
        if ($_SERVER['REMOTE_USER']) {
            $perm = auth_quickaclcheck($pageId);
        } else {
            $perm = auth_aclcheck($pageId, '', null);
        }

        if ($perm >= AUTH_EDIT) {
            return true;
        } else {
            return false;
        }

    }

    public static function isAdmin()
    {
        global $INFO;
        if (!empty($INFO)) {
            return $INFO['isadmin'];
        } else {
            return auth_isadmin(self::getUser(), self::getUserGroups());
        }
    }

    public static function isMember($group)
    {

        return auth_isMember($group, self::getUser(), self::getUserGroups());

    }

    public static function isManager()
    {
        global $INFO;
        if ($INFO !== null) {
            return $INFO['ismanager'];
        } else {
            /**
             * In test
             */
            return auth_ismanager();
        }
    }

    public static function getUser(): string
    {
        global $INPUT;
        $user = $INPUT->server->str('REMOTE_USER');
        if (empty($user)) {
            return "Anonymous";
        }
        return $user;
    }

    private static function getUserGroups()
    {
        global $USERINFO;
        return is_array($USERINFO) ? $USERINFO['grps'] : array();
    }

    /**
     * @param Doku_Form $form
     * @param string $classPrefix
     * @param bool $includeLogo
     * @return string
     */
    public static function getHeaderHTML(Doku_Form $form, $classPrefix, $includeLogo = true)
    {
        if (isset($form->_content[0]["_legend"])) {

            $title = $form->_content[0]["_legend"];
            /**
             * Logo
             */
            $logoHtmlImgTag = "";
            if (
                PluginUtility::getConfValue(Identity::CONF_ENABLE_LOGO_ON_IDENTITY_FORMS, 1)
                &&
                $includeLogo === true
            ) {
                $logoHtmlImgTag = Site::getLogoHtml();
            }
            /**
             * Don't use `header` in place of
             * div because this is a HTML5 tag
             *
             * On php 5.6, the php test library method {@link \phpQueryObject::htmlOuter()}
             * add the below meta tag
             * <meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
             *
             */
            return <<<EOF
<div class="$classPrefix-header">
    $logoHtmlImgTag
    <h1>$title</h1>
</div>
EOF;
        }
        return "";
    }

    public static function isReader(string $pageId): bool
    {
        $perm = self::getPerm($pageId);

        if ($perm >= AUTH_READ) {
            return true;
        } else {
            return false;
        }

    }

    private static function getPerm(string $pageId)
    {
        if ($pageId == null) {
            $pageId = Page::createPageFromRequestedPage()->getDokuwikiId();
        }
        if ($_SERVER['REMOTE_USER']) {
            $perm = auth_quickaclcheck($pageId);
        } else {
            $perm = auth_aclcheck($pageId, '', null);
        }
        return $perm;
    }

    public static function addPrimaryColorCssRuleIfSet(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }
        $primaryColor = Site::getPrimaryColorValue();
        if ($primaryColor !== null) {
            $identityClass = self::FORM_IDENTITY_CLASS;
            $content .= <<<EOF
.$identityClass button[type="submit"]{
   background-color: $primaryColor;
   border-color: $primaryColor;
}
EOF;
        }
        return $content;
    }

    public static function getHtmlStyleTag(string $componentId): string
    {
        $loginCss = Snippet::createInternalCssSnippet($componentId);
        $content = $loginCss->getInternalInlineAndFileContent();
        $content = Identity::addPrimaryColorCssRuleIfSet($content);
        $class = $loginCss->getClass();
        return <<<EOF
<style class="$class">
$content
</style>
EOF;

    }

    public static function addIdentityClass(&$class, string $formClass)
    {

        $formClass = Identity::FORM_IDENTITY_CLASS . " " . $formClass;
        if (isset($class)) {
            $class .= " " . $formClass;
        } else {
            $class = $formClass;
        }

    }


}
