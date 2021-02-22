/*
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

// Webcode
// Will set the height of an iframe to his content
// If the attribute is not set
window.addEventListener("load", function(event) {

    // Select the iframe element with the class webCode
    var webCodeIFrames = document.querySelectorAll("iframe.webCode");

    // Set the height of the iframe to be the height of the internal iframe
    if (webCodeIFrames!=null) {
        for (i = 0; i < webCodeIFrames.length; i++) {
            var webCodeIFrame = webCodeIFrames[i];
            var height = webCodeIFrame.getAttribute('height');
            if (height == null) {
                webCodeIFrame.height = webCodeIFrame.contentWindow.document.querySelector("html").offsetHeight
            }
        }
    }


});
