<?php

use ComboStrap\ColorRgb;
use ComboStrap\Mime;
use ComboStrap\Site;



/**
 *
 * To add the manifest image
 *
 * https://www.dokuwiki.org/devel:manifest
 *
 * @see <a href="https://combostrap.com/manifest">manifest</a>
 *
 * [[doku>devel:manifest|webmanifest]]
 * https://developer.mozilla.org/en-US/docs/Web/Manifest
 */
class action_plugin_combo_manifest extends DokuWiki_Action_Plugin
{


    function register(Doku_Event_Handler $controller)
    {

        /* This will call the function _manifest */
        $controller->register_hook(
            'MANIFEST_SEND',
            'AFTER',
            $this,
            '_manifest',
            array()
        );


    }


    /**
     * Main function; dispatches the visual comment actions
     * @param   $event Doku_Event
     *
     * We take into account the file generated by https://realfavicongenerator.net/
     *
     *
     *
     */
    function _manifest(&$event, $param)
    {

        $mediaId = ":android-chrome-192x192.png";
        $mediaFile = mediaFN($mediaId);
        if (file_exists($mediaFile)) {
            $url = ml($mediaId, '', true, '', true);
            $event->data['icons'][] =
                array(
                    "src" => $url,
                    "sizes" => "192x192",
                    "type" => "image/png"
                );
        }

        $primaryColor = Site::getPrimaryColor();
        if ($primaryColor !== null) {
            $event->data["theme_color"] = $primaryColor->toRgbHex();
        }

        /**
         * Id setting
         * https://developer.chrome.com/blog/pwa-manifest-id/
         * It seems that this is a unique id domain based
         * We set then the start_url (ie another pwa may be living on another path)
         */
        $event->data["id"] = $event->data["start_url"];


        /**
         * Svg must be size any for svg
         * https://html.spec.whatwg.org/multipage/semantics.html#attr-link-sizes
         * otherwise we get this kind of error in devtool.
         * ``
         * Actual Size (150x150)px of Icon .... svg does not  match the specified size (17x17, 512x512)
         * ``
         * Note:
         *   * 150x150 is not the true size
         *   * (17x17, 512x512) is set by dokuwiki
         */
        foreach ($event->data['icons'] as &$iconArray) {
            if ($iconArray["type"] === Mime::SVG) {
                $iconArray["sizes"] = "any";
            }
        }


    }


}
