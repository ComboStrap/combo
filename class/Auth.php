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
     * @param null $user
     */
    public static function becomeSuperUser(&$request,$user = null)
    {
        global $conf;
        $conf['useacl'] = 1;
        if ($user!=null) {
            $user = 'admin';
            $conf['superuser'] = $user;
        }

        // $_SERVER[] = $user;
        $request->setServer('REMOTE_USER', $conf['superuser']);

        // global $USERINFO;
        // $USERINFO['grps'] = array('admin', 'user');

        // global $INFO;
        // $INFO['ismanager'] = true;

    }

    /**
     * @param $request
     * @param string $user - the user to login
     */
    public static function logIn(&$request, $user='defaultUser')
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

}
