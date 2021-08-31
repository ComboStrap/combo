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

use dokuwiki\Menu\Item\AbstractItem;

/**
 * Class MenuItem
 * *
 * @package ComboStrap
 *
 *
 */
class TraceMenuItem extends AbstractItem {





    /**
     *
     * @return string
     */
    public function getLabel() {
        return "Recent Pages Visited";
    }

    public function getLinkAttributes($classprefix = 'menuitem ')
    {

        $linkAttributes = parent::getLinkAttributes($classprefix);
        $linkAttributes['id']= "trace_item_id";
        $linkAttributes['href'] = "#";
        $linkAttributes['data-bs-toggle']="popover";
        $linkAttributes['data-bs-toggle']="popover";
        $linkAttributes['data-bs-content']="Hallo";
        // Dismiss on next click
        $linkAttributes['data-bs-trigger']="focus";
        // See for the tabindex
        // https://getbootstrap.com/docs/5.1/components/popovers/#dismiss-on-next-click
        $linkAttributes['tabindex']="0";
        return $linkAttributes;
    }

    public function getTitle()
    {
        return "Show a list of the navigation history";
    }

    public function getSvg()
    {
        /** @var string icon file */
        return Resources::getImagesDirectory() . '/history.svg';
    }


}
