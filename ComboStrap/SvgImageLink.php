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

    const CANONICAL = ImageFetchSvg::CANONICAL;

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
     * @var ImageFetchSvg|ImageRasterFetch
     */
    private $svgFetch;


    /**
     * SvgImageLink constructor.
     * @param Path $path
     * @param TagAttributes|null $tagAttributes
     * @throws ExceptionBadArgument
     */
    public function __construct(Path $path, TagAttributes $tagAttributes = null)
    {

        if ($tagAttributes === null) {
            $tagAttributes = TagAttributes::createEmpty(self::CANONICAL);
        }
        $tagAttributes->setLogicalTag(self::CANONICAL);

        /**
         * Build the first fetch
         */
        $this->svgFetch = new ImageFetchSvg($path);
        $this->svgFetch->buildSharedImagePropertyFromTagAttributes($tagAttributes);

        parent::__construct($path, $tagAttributes);

    }


    /**
     */
    private function createImgHTMLTag(): string
    {


        $lazyLoad = $this->getLazyLoad();

        $svgInjection = PluginUtility::getConfValue(self::CONF_SVG_INJECTION_ENABLE, 1);
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
                ->attachJavascriptLibraryForSlot(
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
         * @var ImageFetchSvg $image
         */
        $image = $this->getDefaultImageFetch();
        $responseAttributes = TagAttributes::createFromTagAttributes($image->getAttributes());
        $responseAttributes->removeComponentAttributeIfPresent(ImageFetch::CACHE_KEY);

        /**
         * Remove linking (not yet implemented)
         */
        $responseAttributes->removeComponentAttributeIfPresent(MediaLink::LINKING_KEY);


        /**
         * Adaptive Image
         * It adds a `height: auto` that avoid a layout shift when
         * using the img tag
         */
        $responseAttributes->addClassName(RasterImageLink::RESPONSIVE_CLASS);


        /**
         * Alt is mandatory
         */
        $responseAttributes->addOutputAttributeValue("alt", $image->getAltNotEmpty());


        /**
         * Class management
         *
         * functionalClass is the class used in Javascript
         * that should be in the class attribute
         * When injected, the other class should come in a `data-class` attribute
         */
        $svgFunctionalClass = "";
        if ($svgInjection && $lazyLoad) {
            $snippetManager->attachInternalJavascriptForSlot("lozad-svg-injection");
            $svgFunctionalClass = StyleUtility::getStylingClassForTag("lazy-svg-injection");
        } else if ($lazyLoad && !$svgInjection) {
            $snippetManager->attachInternalJavascriptForSlot("lozad-svg");
            $svgFunctionalClass = StyleUtility::getStylingClassForTag("lazy-svg-cs");
        } else if ($svgInjection && !$lazyLoad) {
            $snippetManager->attachInternalJavascriptForSlot("svg-injector");
            $svgFunctionalClass = StyleUtility::getStylingClassForTag("svg-injection");
        }
        if ($lazyLoad) {
            // A class to all component lazy loaded to download them before print
            $svgFunctionalClass .= " " . LazyLoad::getLazyClass();
        }
        $responseAttributes->addClassName($svgFunctionalClass);

        /**
         * Dimension are mandatory
         * to avoid layout shift (CLS)
         */
        $responseAttributes->addOutputAttributeValue(Dimension::WIDTH_KEY, $image->getTargetWidth());
        $responseAttributes->addOutputAttributeValue(Dimension::HEIGHT_KEY, $image->getTargetHeight());

        /**
         * Src call
         */
        $srcValue = $image->getFetchUrl();
        if ($lazyLoad) {

            /**
             * Note: Responsive image srcset is not needed for svg
             */
            $responseAttributes->addOutputAttributeValue("data-src", $srcValue);
            $responseAttributes->addOutputAttributeValue("src", LazyLoad::getPlaceholder(
                $image->getTargetWidth(),
                $image->getTargetHeight()
            ));

        } else {

            $responseAttributes->addOutputAttributeValue("src", $srcValue);

        }

        /**
         * Old model where dokuwiki parses the src in handle
         */
        $responseAttributes->removeAttributeIfPresent(PagePath::PROPERTY_NAME);

        /**
         * Ratio is an attribute of the request, not or rendering
         */
        $responseAttributes->removeAttributeIfPresent(Dimension::RATIO_ATTRIBUTE);

        /**
         * Return the image
         */
        return '<img ' . $responseAttributes->toHTMLAttributeString() . '/>';

    }


    /**
     * Render a link
     * Snippet derived from {@link \Doku_Renderer_xhtml::internalmedia()}
     * A media can be a video also
     * @return string
     * @throws ExceptionNotFound
     */
    public function renderMediaTag(): string
    {

        /**
         * @var ImageFetchSvg $image
         */
        $image = $this->getDefaultImageFetch();
        if (!$image->exists()) {
            throw new ExceptionNotFound("The image ($image) does not exist");
        }

        /**
         * This attributes should not be in the render
         */
        $attributes = $this->getDefaultImageFetch()->getAttributes();
        $attributes->removeComponentAttributeIfPresent(MediaLink::MEDIA_DOKUWIKI_TYPE);
        $attributes->removeComponentAttributeIfPresent(MediaLink::DOKUWIKI_SRC);
        /**
         * TODO: Title should be a node just below SVG
         */
        $attributes->removeComponentAttributeIfPresent(PageTitle::PROPERTY_NAME);

        $imageSize = FileSystems::getSize($image->getPath());
        /**
         * Svg Style conflict:
         * when two svg are created and have a style node, they inject class
         * that may conflict with others (ie cls-1 class, ...)
         * The svg is then inserted via an img tag to scope it.
         */
        $preserveStyle = $attributes->getValue(SvgDocument::PRESERVE_ATTRIBUTE, false);
        $asImgTag = $imageSize > $this->getMaxInlineSize() || $preserveStyle;
        if ($asImgTag) {

            /**
             * Img tag
             */
            $imgHTML = $this->createImgHTMLTag();

        } else {

            /**
             * Svg tag
             */
            $imgHTML = FileSystems::getContent($image->getFetchPath());


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


}
