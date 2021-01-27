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
     */
    public static function becomeSuperUser(&$request)
    {
        global $conf;
        $request->setServer('REMOTE_USER', $conf['superuser']);
    }

}
