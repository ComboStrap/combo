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
use dokuwiki\Form\Form;
use dokuwiki\Form\InputElement;
use dokuwiki\Ui\UserProfile;
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
    public const FIELD_SET_TO_DELETE = ["fieldsetopen", "fieldsetclose"];

    /**
     * Is logged in
     * @return boolean
     */
    public static function isLoggedIn(): bool
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
        }

        /**
         * used by {@link getSecurityToken()}
         */
        global $INPUT;
        $INPUT->server->set('REMOTE_USER', $user);
        // same as $_SERVER['REMOTE_USER'] = $user;

        // global $INFO;
        // $INFO['ismanager'] = true;


        /**
         *
         * Userinfo
         *
         * Email is Mandatory otherwise the {@link UserProfile}
         * does not work
         *
         * USERINFO is also available via $INFO['userinfo']
         * See {@link basicinfo()}
         */
        global $USERINFO;
        $USERINFO['mail'] = "email@example.com";
        // $USERINFO['grps'] = array('admin', 'user');


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
    public static function isWriter($wikiId = null): bool
    {

        if ($wikiId === null) {
            $executionContext = ExecutionContext::getActualOrCreateFromEnv();
            try {
                $wikiId = $executionContext->getRequestedPath()->getWikiId();
            } catch (ExceptionNotFound $e) {
                return false;
            }
        }
        /**
         * There is also
         * $INFO['writable'] === true
         * See true if writable See https://www.dokuwiki.org/devel:environment#info
         */
        if ($_SERVER['REMOTE_USER']) {
            $perm = auth_quickaclcheck($wikiId);
        } else {
            $perm = auth_aclcheck($wikiId, '', null);
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

    public static function isManager(): bool
    {

        return auth_ismanager();

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
        return is_array($USERINFO) && isset($USERINFO['grps']) ? $USERINFO['grps'] : array();
    }

    public static function isReader(string $wikiId): bool
    {
        $perm = self::getPermissions($wikiId);

        if ($perm >= AUTH_READ) {
            return true;
        } else {
            return false;
        }

    }

    private static function getPermissions(string $wikiId): int
    {
        if ($wikiId == null) {
            $wikiId = MarkupPath::createFromRequestedPage()->getWikiId();
        }
        if ($_SERVER['REMOTE_USER']) {
            $perm = auth_quickaclcheck($wikiId);
        } else {
            $perm = auth_aclcheck($wikiId, '', null);
        }
        return $perm;
    }

    public static function getSecurityTokenForAdminUser(): string
    {
        $request = null;
        Identity::becomeSuperUser($request, 'admin');
        return getSecurityToken();
    }


}
