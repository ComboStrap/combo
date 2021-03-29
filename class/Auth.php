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


use TestRequest;

class Auth
{

    /**
     * Is logged in
     * @param $user
     * @return boolean
     */
    public static function isLoggedIn($user = null)
    {
        $loggedIn = false;
        if (!empty($user)) {
            $loggedIn = true;
        } else {
            global $INPUT;
            if ($INPUT->server->has('REMOTE_USER')) {
                $loggedIn = true;
            }
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

        if($request!=null) {
            $request->setServer('REMOTE_USER', $user);
        } else {
            global $INPUT;
            $INPUT->server->set('REMOTE_USER',$user);
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

    }

    /**
     * @return bool if edit auth
     */
    public static function isWriter()
    {

        return auth_quickaclcheck(PluginUtility::getPageId()) >= AUTH_EDIT;

    }

    public static function isAdmin()
    {
        global $INFO;
        return $INFO['isadmin'];
    }

    public static function isManager()
    {
        global $INFO;
        return $INFO['ismanager'];
    }


}
