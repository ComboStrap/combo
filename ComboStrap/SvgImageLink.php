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


/**
 * Image
 * This is the class that handles the
 * svg link type
 */
class SvgImageLink extends ImageLink
{

    const CANONICAL = FetcherSvg::CANONICAL;

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
    const TAG = "svg";


    private ?FetcherSvg $svgFetch = null;

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
     * @throws ExceptionNotFound
     */
    private function createImgHTMLTag(): string
    {


        $lazyLoad = $this->isLazyLoaded();

        $svgInjection = Site::getConfValue(self::CONF_SVG_INJECTION_ENABLE, 1);

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

        // Add lazy load snippet
        if ($lazyLoad) {
            LazyLoad::addLozadSnippet();
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
         * Class management
         *
         * functionalClass is the class used in Javascript
         * that should be in the class attribute
         * When injected, the other class should come in a `data-class` attribute
         */
        $svgFunctionalClass = "";
        if ($svgInjection && $lazyLoad) {
            $snippetManager->attachJavascriptFromComponentId("lozad-svg-injection");
            $svgFunctionalClass = StyleUtility::addComboStrapSuffix("lazy-svg-injection");
        } else if ($lazyLoad && !$svgInjection) {
            $snippetManager->attachJavascriptFromComponentId("lozad-svg");
            $svgFunctionalClass = StyleUtility::addComboStrapSuffix("lazy-svg-cs");
        } else if ($svgInjection && !$lazyLoad) {
            $snippetManager->attachJavascriptFromComponentId("svg-injector");
            $svgFunctionalClass = StyleUtility::addComboStrapSuffix("svg-injection");
        }
        if ($lazyLoad) {
            // A class to all component lazy loaded to download them before print
            $svgFunctionalClass .= " " . LazyLoad::getLazyClass();
        }
        $imgAttributes->addClassName($svgFunctionalClass);


        /**
         * @var FetcherSvg $svgFetch
         */
        $svgFetch = $this->mediaMarkup->getFetcher();
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
         * Src call
         */
        $srcValue = $svgFetch->getFetchUrl();
        if ($lazyLoad) {

            /**
             * Note: Responsive image srcset is not needed for svg
             */
            $imgAttributes->addOutputAttributeValue("data-src", $srcValue);
            $imgAttributes->addOutputAttributeValue("src", LazyLoad::getPlaceholder(
                $svgFetch->getTargetWidth(),
                $svgFetch->getTargetHeight()
            ));

        } else {

            $imgAttributes->addOutputAttributeValue("src", $srcValue);

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

    private function getMaxInlineSize()
    {
        return Site::getConfValue(self::CONF_MAX_KB_SIZE_FOR_INLINE_SVG, 2) * 1024;
    }


    public function isLazyLoaded()
    {
        try {
            return $this->mediaMarkup->isLazy();
        } catch (ExceptionNotFound $e) {
            return Site::getConfValue(SvgImageLink::CONF_LAZY_LOAD_ENABLE);
        }
    }

}
