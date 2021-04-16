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

require_once(__DIR__ . '/InternalMediaLink.php');
require_once(__DIR__ . '/PluginUtility.php');
require_once(__DIR__ . '/SvgDocument.php');

/**
 * Image
 * This is the class that handles the
 * svg link type
 */
class SvgImageLink extends InternalMediaLink
{

    const CANONICAL = "svg";

    /**
     * The maximum size to be embedded
     * Above this size limit they are fetched
     */
    const CONF_MAX_KB_SIZE_FOR_INLINE_SVG = "svgMaxInlineSize";

    /**
     * Lazy Load
     */
    const CONF_LAZY_LOAD_ENABLE = "svgLazyLoadEnable";
    /**
     * Svg Injection
     */
    const CONF_SVG_INJECTION_ENABLE = "svgInjectionEnable";


    private $svgWidth;
    /**
     * @var int
     */
    private $svgWeight;


    private function createImgHTMLTag($tagAttributes = null)
    {
        if ($tagAttributes == null) {
            $tagAttributes = TagAttributes::createEmpty();
        }

        $imgHTML = '<img';

        $lazyLoad = $this->getLazyLoad();
        $svgInjection = PluginUtility::getConfValue(self::CONF_SVG_INJECTION_ENABLE, 1);
        /**
         * Snippet
         */
        if ($svgInjection) {
            $snippetManager = PluginUtility::getSnippetManager();

            // Based on https://github.com/iconic/SVGInjector/
            // See also: https://github.com/iconfu/svg-inject
            // !! There is a fork: https://github.com/tanem/svg-injector !!
            // Fallback ? : https://github.com/iconic/SVGInjector/#per-element-png-fallback
            $snippetManager->upsertTagsForBar("svg-injector",
                array(
                    'script' => [
                        array(
                            "src" => "https://cdn.jsdelivr.net/npm/svg-injector@1.1.3/dist/svg-injector.min.js",
                            "integrity" => "sha256-CjBlJvxqLCU2HMzFunTelZLFHCJdqgDoHi/qGJWdRJk=",
                            "crossorigin" => "anonymous"
                        )
                    ]
                )
            );
        }

        if ($lazyLoad) {

            // Add lazy load snippet
            LazyLoad::addLozadSnippet();
        }

        $injectionClass = "";
        if ($svgInjection && $lazyLoad) {
            PluginUtility::getSnippetManager()->upsertJavascriptForBar("lozad-svg-injection");
            $injectionClass = "combo-lazy-svg-injection";
        } else if ($lazyLoad && !$svgInjection) {
            PluginUtility::getSnippetManager()->upsertJavascriptForBar("lozad-svg");
            $injectionClass = "combo-lazy-svg";
        } else if ($svgInjection && !$lazyLoad) {
            PluginUtility::getSnippetManager()->upsertJavascriptForBar("svg-injector");
            $injectionClass = "combo-svg-injection";
        }

        /**
         * Style properties
         * TODO: when {@link TagAttributes} supports the creation of style, use it instead
         */
        $styleProperties = "";
        $widthValue = $this->getImgTagWidthValue();
        if (!empty($widthValue)) {
            $styleProperties .= 'max-width:' . $this->getImgTagWidthValue() . 'px';
        }
        if (!empty($this->getImgTagHeightValue())) {
            if (!empty($styleProperties)) {
                $styleProperties .= ";";
            }
            $styleProperties .= 'height:' . $this->getImgTagHeightValue() . 'px';
        }
        if (!empty($styleProperties)) {
            $imgHTML .= ' style="' . $styleProperties . '"';
        }

        /**
         * Class processing
         * TODO: When the processing will attached total to tag attributes
         */
        PluginUtility::processAlignAttributes($tagAttributes);


        /**
         * Class
         */
        if ($svgInjection) {
            $imgHTML .= ' class="' . $injectionClass . '"';
            if ($tagAttributes->hasComponentAttribute("class")) {
                $imgHTML .= ' data-class="' . $tagAttributes->getClass() . '"';
            }
        } else {
            $allClass = $injectionClass;
            if ($tagAttributes->hasComponentAttribute("class")) {
                $allClass .= ' ' . $tagAttributes->getClass();
            }
            $imgHTML .= ' class="' . $allClass . '"';
        }


        /**
         * Src
         */
        $srcValue = $this->getUrl();
        if ($lazyLoad) {

            $imgHTML .= " data-src=\"$srcValue\"";


            /**
             * Responsive image src set
             * is not needed for svg
             */


        } else {

            $imgHTML .= " src=\"$srcValue\"";
        }


        /**
         * Title
         */
        if (!empty($this->getTitle())) {
            $imgHTML .= ' alt = "' . $this->getTitle() . '"';
        }


        $imgHTML .= '>';
        return $imgHTML;
    }


    /**
     * @param bool $absolute - use for semantic data
     * @return string|null
     */
    public function getUrl($absolute = true)
    {

        if ($this->exists()) {

            /**
             * Link attribute
             * No width and height
             * There are embedded in the inline
             * or set in the img element
             * No need to resize, the browser do it
             */
            $att = array();
            if ($this->getCache()) {
                $att['cache'] = $this->getCache();
            }
            /**
             * Width and height are extern style properties
             * Needed if the SVG is injected
             * They are not the width and height of the SVG
             * because the svg can fit
             * They are more the max-width notion
             */
//            if (!empty($this->getRequestedWidth())){
//                $att['w'] = $this->getRequestedWidth();
//            }
            $direct = true;
            return ml($this->getId(), $att, $direct, '&', $absolute);

        } else {

            return null;

        }
    }

    public function getAbsoluteUrl()
    {

        return $this->getUrl(true);

    }


    /**
     * Render a link
     * Snippet derived from {@link \Doku_Renderer_xhtml::internalmedia()}
     * A media can be a video also (Use
     * @param $tagAttributes
     * @return string
     */
    public function renderMediaTag(&$tagAttributes = null)
    {

        /**
         * To init the properties
         */
        parent::renderMediaTag($tagAttributes);

        if ($this->exists()) {


            if (
                $this->getSize() > $this->getMaxInlineSize()
            ) {

                $imgHTML = $this->createImgHTMLTag($tagAttributes);

            } else {

                $imgHTML = file_get_contents($this->getSvgFile($tagAttributes));

            }


        } else {

            $imgHTML = "<span class=\"text-danger\">The svg ($this) does not exist</span>";

        }
        return $imgHTML;
    }

    /**
     * @return int - the width of the image from the file
     */
    public function getMediaWidth()
    {
        return $this->svgWidth;
    }

    /**
     * @return int - the height of the image from the file
     */
    public function getMediaHeight()
    {
        return $this->svgWeight;
    }


    /**
     * @return int - the width value attribute in a img
     */
    private function getImgTagWidthValue()
    {
        $linkWidth = $this->getRequestedWidth();
        if (empty($linkWidth)) {
            if (empty($this->getRequestedHeight())) {

                $linkWidth = $this->getMediaWidth();

            } else {

                // Height is not empty
                // We derive the width from it
                if ($this->getMediaHeight() != 0
                    && !empty($this->getMediaHeight())
                    && !empty($this->getMediaWidth())
                ) {
                    $linkWidth = $this->getMediaWidth() * ($this->getRequestedHeight() / $this->getMediaHeight());
                }

            }
        }
        /**
         * Rounding to integer
         * The fetch.php file takes int as value for width and height
         * making a rounding if we pass a double (such as 37.5)
         * This is important because the security token is based on width and height
         * and therefore the fetch will failed
         */
        return intval($linkWidth);
    }

    /**
     * @return int the height value attribute in a img
     */
    private function getImgTagHeightValue()
    {

        /**
         * Height default
         */
        $linkHeight = $this->getRequestedHeight();
        if (empty($linkHeight)) {
            $linkHeight = $this->getMediaHeight();
        }

        /**
         * Scale the height by size parameter
         */
        if (!empty($linkHeight) &&
            !empty($localWidth) &&
            !empty($this->getMediaWidth()) &&
            $this->getMediaWidth() != 0
        ) {
            $linkHeight = $linkHeight * ($localWidth / $this->getMediaWidth());
        }

        /**
         * Rounding to integer
         * The fetch.php file takes int as value for width and height
         * making a rounding if we pass a double (such as 37.5)
         * This is important because the security token is based on width and height
         * and therefore the fetch will failed
         */
        return intval($linkHeight);

    }

    private function getMaxInlineSize()
    {
        return PluginUtility::getConfValue(self::CONF_MAX_KB_SIZE_FOR_INLINE_SVG, 2) * 1024;
    }






    public function getLazyLoad()
    {
        $lazyLoad = parent::getLazyLoad();
        if ($lazyLoad !== null) {
            return $lazyLoad;
        } else {
            return PluginUtility::getConfValue(SvgImageLink::CONF_LAZY_LOAD_ENABLE);
        }
    }

    /**
     * @param TagAttributes $tagAttributes
     */
    public function getSvgFile($tagAttributes)
    {

        $cache = new Cache($this, $tagAttributes);
        if (!$cache->cacheUsable()) {
            $content = SvgDocument::createFromPath($this)->getXmlText($tagAttributes);
            $cache->storeCache($content);
        }
        return $cache->getFile()->getPath();

    }

}
