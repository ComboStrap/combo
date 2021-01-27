<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


class ColorUtility
{

    const COLOR = "color";
    const BACKGROUND_COLOR = "background-color";
    const BORDER_COLOR = "border-color";

    static $colors = array(
        "info" => array(
            self::COLOR => "#0c5460",
            self::BACKGROUND_COLOR => "#d1ecf1",
            self::BORDER_COLOR => "#bee5eb"
        ),
        "tip" =>  array(
            self::COLOR => "#6c6400",
            self::BACKGROUND_COLOR => "#fff79f",
            self::BORDER_COLOR => "#FFF78c"
        ),
        "warning" =>  array(
            self::COLOR => "#856404",
            self::BACKGROUND_COLOR => "#fff3cd",
            self::BORDER_COLOR => "#ffeeba"
        ),
        "primary" =>  array(
            self::COLOR => "#fff",
            self::BACKGROUND_COLOR => "#007bff",
            self::BORDER_COLOR => "#007bff"
        ),
        "secondary" =>  array(
            self::COLOR => "#fff",
            self::BACKGROUND_COLOR => "#6c757d",
            self::BORDER_COLOR => "#6c757d"
        ),
        "success" =>  array(
            self::COLOR => "#fff",
            self::BACKGROUND_COLOR => "#28a745",
            self::BORDER_COLOR => "#28a745"
        ),
        "danger" =>  array(
            self::COLOR => "#fff",
            self::BACKGROUND_COLOR => "#dc3545",
            self::BORDER_COLOR => "#dc3545"
        ),
        "dark" =>  array(
            self::COLOR => "#fff",
            self::BACKGROUND_COLOR => "#343a40",
            self::BORDER_COLOR => "#343a40"
        ),
        "light" =>  array(
            self::COLOR => "#fff",
            self::BACKGROUND_COLOR => "#f8f9fa",
            self::BORDER_COLOR => "#f8f9fa"
        )
    );
}
