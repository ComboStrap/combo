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


use ComboStrap\TagAttribute\StyleAttribute;

/**
 * Image
 * This is the class that handles the
 * svg link type
 */
class SvgImageLink extends ImageLink
{

    const CANONICAL = FetcherSvg::CANONICAL;

    /**
     * Lazy Load
     */
    const CONF_LAZY_LOAD_ENABLE = "svgLazyLoadEnable";

    /**
     * Svg Injection
     */
    const CONF_SVG_INJECTION_ENABLE = "svgInjectionEnable";
    /**
     * Svg Injection Default
     * For now, there is a FOUC when the svg is visible,
     * The image does away, the layout shift, the image comes back, the layout shift
     * We disabled it by default then
     */
    const CONF_SVG_INJECTION_ENABLE_DEFAULT = 0;
    const TAG = "svg";


    /**
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     * @throws ExceptionNotExists
     */
    public static function createFromFetcher(FetcherSvg $fetchImage)
    {
        return SvgImageLink::createFromMediaMarkup(MediaMarkup::createFromFetcher($fetchImage));
    }


    /**
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     */
    private function createImgHTMLTag(): string
    {


        $svgInjection = ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->getBooleanValue(self::CONF_SVG_INJECTION_ENABLE, self::CONF_SVG_INJECTION_ENABLE_DEFAULT);

        /**
         * Snippet
         */
        $snippetManager = PluginUtility::getSnippetManager();
        if ($svgInjection) {

            // Based on https://github.com/iconic/SVGInjector/
            // See also: https://github.com/iconfu/svg-inject
            // !! There is a fork: https://github.com/tanem/svg-injector !!
            // Fallback ? : https://github.com/iconic/SVGInjector/#per-element-png-fallback
            $snippetManager
                ->attachRemoteJavascriptLibrary(
                    "svg-injector",
                    "https://cdn.jsdelivr.net/npm/svg-injector@1.1.3/dist/svg-injector.min.js",
                    "sha256-CjBlJvxqLCU2HMzFunTelZLFHCJdqgDoHi/qGJWdRJk="
                )
                ->setDoesManipulateTheDomOnRun(false);

        }


        /**
         * Remove the cache attribute
         * (no cache for the img tag)
         * @var FetcherSvg $image
         */
        $imgAttributes = $this->mediaMarkup->getExtraMediaTagAttributes()
            ->setLogicalTag(self::TAG);

        /**
         * Adaptive Image
         * It adds a `height: auto` that avoid a layout shift when
         * using the img tag
         */
        $imgAttributes->addClassName(RasterImageLink::RESPONSIVE_CLASS);


        /**
         * Alt is mandatory
         */
        $imgAttributes->addOutputAttributeValue("alt", $this->getAltNotEmpty());


        /**
         * @var FetcherSvg $svgFetch
         */
        $svgFetch = $this->mediaMarkup->getFetcher();
        $srcValue = $svgFetch->getFetchUrl();

        /**
         * Class management
         *
         */
        $lazyLoad = $this->isLazyLoaded();
        if ($lazyLoad) {
            // A class to all component lazy loaded to download them before print
            $imgAttributes->addClassName(LazyLoad::getLazyClass());
            $lazyLoadMethod = $this->mediaMarkup->getLazyLoadMethodOrDefault();
            switch ($lazyLoadMethod) {
                case LazyLoad::LAZY_LOAD_METHOD_LOZAD_VALUE:
                    LazyLoad::addLozadSnippet();
                    if ($svgInjection) {
                        $snippetManager->attachJavascriptFromComponentId("lozad-svg-injection");
                        $imgAttributes->addClassName(StyleAttribute::addComboStrapSuffix("lazy-svg-injection"));
                    } else {
                        $snippetManager->attachJavascriptFromComponentId("lozad-svg");
                        $imgAttributes->addClassName(StyleAttribute::addComboStrapSuffix("lazy-svg"));
                    }
                    /**
                     * Note: Responsive image srcset is not needed for svg
                     */
                    $imgAttributes->addOutputAttributeValue("data-src", $srcValue);
                    $imgAttributes->addOutputAttributeValue("src", LazyLoad::getPlaceholder(
                        $svgFetch->getTargetWidth(),
                        $svgFetch->getTargetHeight()
                    ));
                    break;
                case LazyLoad::LAZY_LOAD_METHOD_HTML_VALUE:
                    $imgAttributes->addOutputAttributeValue("loading", "lazy");
                    $imgAttributes->addOutputAttributeValue("src", $srcValue);
                    break;
            }

        } else {
            if ($svgInjection) {
                $snippetManager->attachJavascriptFromComponentId("svg-injector");
                $imgAttributes->addClassName(StyleAttribute::addComboStrapSuffix("svg-injection"));
            }
            $imgAttributes->addOutputAttributeValue("src", $srcValue);
        }



        /**
         * Dimension are mandatory on the image
         * to avoid layout shift (CLS)
         * We add them as output attribute
         */
        $imgAttributes->addOutputAttributeValue(Dimension::WIDTH_KEY, $svgFetch->getTargetWidth());
        $imgAttributes->addOutputAttributeValue(Dimension::HEIGHT_KEY, $svgFetch->getTargetHeight());

        /**
         * For styling, we add the width and height as component attribute
         */
        try {
            $imgAttributes->addComponentAttributeValue(Dimension::WIDTH_KEY, $svgFetch->getRequestedWidth());
        } catch (ExceptionNotFound $e) {
            // ok
        }
        try {
            $imgAttributes->addComponentAttributeValue(Dimension::HEIGHT_KEY, $svgFetch->getRequestedHeight());
        } catch (ExceptionNotFound $e) {
            // ok
        }

        /**
         * Return the image
         */
        return $imgAttributes->toHtmlEmptyTag("img");


    }


    /**
     * Render a link
     * Snippet derived from {@link \Doku_Renderer_xhtml::internalmedia()}
     * A media can be a video also
     * @return string
     * @throws ExceptionNotFound
     * @throws ExceptionBadArgument
     */
    public function renderMediaTag(): string
    {


        $imagePath = $this->mediaMarkup->getPath();
        if (!FileSystems::exists($imagePath)) {
            throw new ExceptionNotFound("The image ($imagePath) does not exist");
        }

        /**
         * TODO: Title/Label should be a node just below SVG
         */
        $imageSize = FileSystems::getSize($imagePath);

        /**
         * Svg Style conflict:
         * when two svg are created and have a style node, they inject class
         * that may conflict with others (ie cls-1 class, ...)
         * The svg is then inserted via an img tag to scope it.
         */
        try {
            $preserveStyle = DataType::toBoolean($this->mediaMarkup->getFetcher()->getFetchUrl()->getQueryPropertyValueAndRemoveIfPresent(FetcherSvg::REQUESTED_PRESERVE_ATTRIBUTE));
        } catch (ExceptionNotFound $e) {
            $preserveStyle = false;
        }

        $asImgTag = $imageSize > $this->getMaxInlineSize() || $preserveStyle;
        if ($asImgTag) {

            /**
             * Img tag
             */
            $imgHTML = $this->createImgHTMLTag();

        } else {


            try {
                /**
                 * Svg tag
                 * @var FetcherSvg $fetcherSvg
                 */
                $fetcherSvg = $this->mediaMarkup->getFetcher();
                try {
                    $fetcherSvg->setRequestedClass($this->mediaMarkup->getExtraMediaTagAttributes()->getClass());
                } catch (ExceptionNull $e) {
                    // ok
                }
                $fetchPath = $fetcherSvg->getFetchPath();
                $imgHTML = FileSystems::getContent($fetchPath);
                ExecutionContext::getActualOrCreateFromEnv()
                    ->getSnippetSystem()
                    ->attachCssInternalStyleSheet(DokuWiki::DOKUWIKI_STYLESHEET_ID);
            } catch (ExceptionNotFound|ExceptionBadArgument|ExceptionBadState|ExceptionBadSyntax|ExceptionCompile $e) {
                LogUtility::error("Unable to include the svg in the document. Error: {$e->getMessage()}");
                $imgHTML = $this->createImgHTMLTag();
            }

        }

        return $this->wrapMediaMarkupWithLink($imgHTML);

    }

    /**
     * @return int
     */
    private function getMaxInlineSize()
    {
        return ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->getHtmlMaxInlineResourceSize();
    }


    public function isLazyLoaded(): bool
    {

        if ($this->mediaMarkup->isLazy() === false) {
            return false;
        }
        return SiteConfig::getConfValue(self::CONF_LAZY_LOAD_ENABLE, 1);

    }

}
