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

require_once(__DIR__ . '/MediaLink.php');
require_once(__DIR__ . '/PluginUtility.php');
require_once(__DIR__ . '/SvgDocument.php');

/**
 * Image
 * This is the class that handles the
 * svg link type
 */
class SvgImageLink extends MediaLink
{

    const CANONICAL = "svg";

    /**
     * The maximum size to be embedded
     * Above this size limit they are fetched
     */
    const CONF_MAX_KB_SIZE_FOR_INLINE_SVG = "svgMaxInlineSizeKb";

    /**
     * Lazy Load
     */
    const CONF_LAZY_LOAD_ENABLE = "svgLazyLoadEnable";

    /**
     * Svg Injection
     */
    const CONF_SVG_INJECTION_ENABLE = "svgInjectionEnable";

    /**
     * SvgImageLink constructor.
     * @param $id
     * @param TagAttributes $tagAttributes
     * @param string $rev
     */
    public function __construct($id, $tagAttributes = null, $rev = '')
    {
        parent::__construct($id, $tagAttributes, $rev);
        $this->getTagAttributes()->setTag(self::CANONICAL);
    }


    private function createImgHTMLTag()
    {


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

        // Add lazy load snippet
        if ($lazyLoad) {
            LazyLoad::addLozadSnippet();
        }

        /**
         * Remove the cache attribute
         * (no cache for the img tag)
         */
        $this->tagAttributes->removeComponentAttributeIfPresent(CacheMedia::CACHE_KEY);

        /**
         * Remove linking (not yet implemented)
         */
        $this->tagAttributes->removeComponentAttributeIfPresent(TagAttributes::LINKING_KEY);

        /**
         * Class
         * functionalClass is not added
         * as a normal class when injected
         * This is why, it's not added in the {@link TagAttributes}
         */
        $functionalClass = "";
        if ($svgInjection && $lazyLoad) {
            PluginUtility::getSnippetManager()->attachJavascriptSnippetForBar("lozad-svg-injection");
            $functionalClass = "combo-lazy-svg-injection";
        } else if ($lazyLoad && !$svgInjection) {
            PluginUtility::getSnippetManager()->attachJavascriptSnippetForBar("lozad-svg");
            $functionalClass = "combo-lazy-svg";
        } else if ($svgInjection && !$lazyLoad) {
            PluginUtility::getSnippetManager()->attachJavascriptSnippetForBar("svg-injector");
            $functionalClass = "combo-svg-injection";
        }


        /**
         * Src
         */
        $srcValue = $this->getUrl();
        if ($lazyLoad) {

            $this->tagAttributes->addHtmlAttributeValue("data-src", $srcValue);

            /**
             * Note: Responsive image srcset is not needed for svg
             */

        } else {

            $this->tagAttributes->addHtmlAttributeValue("src", $srcValue);

        }


        /**
         * Title
         */
        if (!empty($this->getTitle())) {
            $this->tagAttributes->addHtmlAttributeValue("alt", $this->getTitle());
        }

        /**
         * Class into data-class for injection
         */
        if ($svgInjection) {
            if ($this->tagAttributes->hasComponentAttribute("class")) {
                $this->tagAttributes->addHtmlAttributeValue("data-class", $this->tagAttributes->getValueAndRemove("class"));
            }
        }
        // Add the functional class
        $this->tagAttributes->addClassName($functionalClass);

        /**
         * Return the image
         */
        return '<img ' . $this->tagAttributes->toHTMLAttributeString() . '/>';

    }


    public function getAbsoluteUrl()
    {

        return $this->getUrl();

    }

    /**
     * @param string $ampersand $absolute - the & separator (should be encoded for HTML but not for CSS)
     * @return string|null
     *
     * At contrary to {@link RasterImageLink::getUrl()} this function does not need any width parameter
     */
    public function getUrl($ampersand = MediaLink::URL_ENCODED_AND)
    {

        if ($this->exists()) {

            /**
             * We remove align and linking because,
             * they should apply only to the img tag
             */


            /**
             *
             * Create the array $att that will cary the query
             * parameter for the URL
             */
            $att = array();
            $componentAttributes = $this->tagAttributes->getComponentAttributes();
            foreach ($componentAttributes as $name => $value) {

                if (!in_array($name, MediaLink::NON_URL_ATTRIBUTES)) {
                    $newName = $name;

                    /**
                     * Width and Height
                     * permits to create SVG of the asked size
                     *
                     * This is a little bit redundant with the
                     * {@link Dimension::processWidthAndHeight()}
                     * `max-width and width` styling property
                     * but you may use them outside of HTML.
                     */
                    switch ($name) {
                        case TagAttributes::WIDTH_KEY:
                            $newName = "w";
                            /**
                             * We don't remove width because,
                             * the sizing should apply to img
                             */
                            break;
                        case TagAttributes::HEIGHT_KEY:
                            $newName = "h";
                            /**
                             * We don't remove height because,
                             * the sizing should apply to img
                             */
                            break;
                    }

                    if ($newName== CacheMedia::CACHE_KEY && $value== CacheMedia::CACHE_DEFAULT_VALUE){
                        // This is the default
                        // No need to add it
                        continue;
                    }

                    if (!empty($value)) {
                        $att[$newName] = trim($value);
                    }
                }

            }

            /**
             * Cache bursting
             */
            if (!$this->tagAttributes->hasComponentAttribute(CacheMedia::CACHE_BUSTER_KEY)) {
                $att[CacheMedia::CACHE_BUSTER_KEY] = $this->getModifiedTime();
            }

            $direct = true;
            return ml($this->getId(), $att, $direct, $ampersand, true);

        } else {

            return null;

        }
    }

    /**
     * Render a link
     * Snippet derived from {@link \Doku_Renderer_xhtml::internalmedia()}
     * A media can be a video also
     * @return string
     */
    public function renderMediaTag()
    {

        if ($this->exists()) {

            /**
             * This attribute should not be in the render
             */
            $this->tagAttributes->removeComponentAttributeIfPresent(MediaLink::MEDIA_DOKUWIKI_TYPE);

            if (
                $this->getSize() > $this->getMaxInlineSize()
            ) {

                /**
                 * Img tag
                 */
                $imgHTML = $this->createImgHTMLTag();

            } else {

                /**
                 * Svg tag
                 */
                $imgHTML = file_get_contents($this->getSvgFile());

            }


        } else {

            $imgHTML = "<span class=\"text-danger\">The svg ($this) does not exist</span>";

        }
        return $imgHTML;
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


    public function getSvgFile()
    {

        $cache = new CacheMedia($this, $this->tagAttributes);
        if (!$cache->isCacheUsable()) {
            $content = SvgDocument::createFromPath($this)->getXmlText($this->tagAttributes);
            $cache->storeCache($content);
        }
        return $cache->getFile()->getFileSystemPath();

    }

}
