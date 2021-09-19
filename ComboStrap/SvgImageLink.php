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


require_once(__DIR__ . '/PluginUtility.php');


/**
 * Image
 * This is the class that handles the
 * svg link type
 */
class SvgImageLink extends ImageLink
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
     * @param ImageSvg $imageSvg
     * @param TagAttributes $tagAttributes
     */
    public function __construct($imageSvg, $tagAttributes = null)
    {
        parent::__construct($imageSvg, $tagAttributes);
        $this->getTagAttributes()->setLogicalTag(self::CANONICAL);

    }


    private function createImgHTMLTag(): string
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
                            "src" => "https://cdn.jsdelivr.net/npm/svg-injector@1.1.3/svg-injector.min.js",
                            // "integrity" => "sha256-CjBlJvxqLCU2HMzFunTelZLFHCJdqgDoHi/qGJWdRJk=",
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
        $this->tagAttributes->removeComponentAttributeIfPresent(MediaLink::LINKING_KEY);


        /**
         * Src
         */
        $srcValue = $this->getDefaultImage()->getUrl(DokuwikiUrl::URL_ENCODED_AND, $this->tagAttributes);
        if ($lazyLoad) {

            /**
             * Note: Responsive image srcset is not needed for svg
             */
            $this->tagAttributes->addHtmlAttributeValue("data-src", $srcValue);
            $this->tagAttributes->addHtmlAttributeValue("src", LazyLoad::getPlaceholder(
                $this->getDefaultImage()->getWidthValueScaledDown($this->getRequestedWidth(), $this->getRequestedHeight()),
                $this->getDefaultImage()->getHeightValueScaledDown($this->getRequestedWidth(), $this->getRequestedHeight()))
            );

        } else {

            $this->tagAttributes->addHtmlAttributeValue("src", $srcValue);

        }

        /**
         * Adaptive Image
         * It adds a `height: auto` that avoid a layout shift when
         * using the img tag
         */
        $this->tagAttributes->addClassName(RasterImageLink::RESPONSIVE_CLASS);


        /**
         * Title
         */
        if (!empty($this->getTitle())) {
            $this->tagAttributes->addHtmlAttributeValue("alt", $this->getTitle());
        }


        /**
         * Class management
         *
         * functionalClass is the class used in Javascript
         * that should be in the class attribute
         * When injected, the other class should come in a `data-class` attribute
         */
        $svgFunctionalClass = "";
        if ($svgInjection && $lazyLoad) {
            PluginUtility::getSnippetManager()->attachJavascriptSnippetForBar("lozad-svg-injection");
            $svgFunctionalClass = "lazy-svg-injection-combo";
        } else if ($lazyLoad && !$svgInjection) {
            PluginUtility::getSnippetManager()->attachJavascriptSnippetForBar("lozad-svg");
            $svgFunctionalClass = "lazy-svg-combo";
        } else if ($svgInjection && !$lazyLoad) {
            PluginUtility::getSnippetManager()->attachJavascriptSnippetForBar("svg-injector");
            $svgFunctionalClass = "svg-injection-combo";
        }
        if ($lazyLoad) {
            // A class to all component lazy loaded to download them before print
            $svgFunctionalClass .= " " . LazyLoad::LAZY_CLASS;
        }
        $this->tagAttributes->addClassName($svgFunctionalClass);

        /**
         * Dimension are mandatory
         * to avoid layout shift (CLS)
         */
        $this->tagAttributes->addHtmlAttributeValue(Dimension::WIDTH_KEY,
            $this->getDefaultImage()->getWidthValueScaledDown(
                $this->getRequestedWidth(),
                $this->getRequestedHeight())
        );
        $this->tagAttributes->addHtmlAttributeValue(
            Dimension::HEIGHT_KEY,
            $this->getDefaultImage()->getHeightValueScaledDown($this->getRequestedWidth(), $this->getRequestedHeight())
        );


        /**
         * Return the image
         */
        return '<img ' . $this->tagAttributes->toHTMLAttributeString() . '/>';

    }


    /**
     * Render a link
     * Snippet derived from {@link \Doku_Renderer_xhtml::internalmedia()}
     * A media can be a video also
     * @return string
     */
    public function renderMediaTag(): string
    {

        $image = $this->getDefaultImage();
        if ($image->exists()) {

            /**
             * This attributes should not be in the render
             */
            $this->tagAttributes->removeComponentAttributeIfPresent(MediaLink::MEDIA_DOKUWIKI_TYPE);
            $this->tagAttributes->removeComponentAttributeIfPresent(MediaLink::DOKUWIKI_SRC);
            /**
             * TODO: Title should be a node just below SVG
             */
            $this->tagAttributes->removeComponentAttributeIfPresent(Page::TITLE_META_PROPERTY);

            if (
                $image->getSize() > $this->getMaxInlineSize()
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
        /**
         * @var ImageSvg $image
         */
        $image = $this->getDokuPath();
        $cache = new CacheMedia($image, $this->tagAttributes);
        if (!$cache->isCacheUsable()) {
            $content = $image->getSvgDocument()->getXmlText($this->tagAttributes);
            $cache->storeCache($content);
        }
        return $cache->getFile()->getFileSystemPath();

    }


}
