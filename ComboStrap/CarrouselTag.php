<?php

namespace ComboStrap;


use syntax_plugin_combo_fragment;

/**
 * Carrousel
 *
 * We loved
 * https://github.com/OwlCarousel2/OwlCarousel2
 * but it's deprecated and
 * send us to
 * https://github.com/ganlanyuan/tiny-slider
 * But it used as gutter the padding not the margin (http://ganlanyuan.github.io/tiny-slider/demo/#gutter_wrapper)
 * Then we found
 * https://glidejs.com/
 *
 * If we need another,
 * * https://swiperjs.com/ - <a href="https://themes.getbootstrap.com/preview/?theme_id=5348">purpose template</a>
 * * https://github.com/ganlanyuan/tiny-slider - https://themes.getbootstrap.com/preview/?theme_id=92520 - blogzine
 *
 *
 */
class CarrouselTag
{
    public const ELEMENT_WIDTH_ATTRIBUTE = "element-width";
    /**
     * To center the image inside a link in a carrousel
     */
    public const MEDIA_CENTER_LINK_CLASS = "justify-content-center align-items-center d-flex";
    public const CONTROL_ATTRIBUTE = "control";
    public const CANONICAL = CarrouselTag::TAG;
    public const ELEMENTS_MIN_ATTRIBUTE = "elements-min";
    public const ELEMENTS_MIN_DEFAULT = 3;
    public const GLIDE_SLIDE_CLASS = "glide__slide";
    public const TAG = 'carrousel';


    /**
     * Glide copy the HTML element and lozad does not see element that are not visible
     * The element non-visible are not processed by lozad
     * We set lazy loading to HTML loading attribute
     */
    public static function setLazyLoadToHtmlOnImageTagUntilTheEndOfTheStack(CallStack $callStack)
    {
        while ($actualCall = $callStack->next()) {
            if ($actualCall->getState() === DOKU_LEXER_SPECIAL && in_array($actualCall->getTagName(), Call::IMAGE_TAGS)) {
                $actualCall->addAttribute(
                    MediaMarkup::LAZY_LOAD_METHOD,
                    MediaMarkup::LAZY_LOAD_METHOD_HTML_VALUE
                );
            }
        }
    }

    public static function handleEnter(\Doku_Handler $handler): array
    {
        $callStack = CallStack::createFromHandler($handler);
        $parent = $callStack->moveToParent();
        $context = null;
        if ($parent !== false) {
            $context = $parent->getTagName();
        }
        return array(PluginUtility::CONTEXT => $context);
    }

    public static function handleExit(\Doku_Handler $handler): array
    {
        $callStack = CallStack::createFromHandler($handler);
        $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
        $actualCall = $callStack->moveToFirstChildTag();
        if ($actualCall !== false) {
            if ($actualCall->getTagName() === FragmentTag::FRAGMENT_TAG) {
                $templateEndCall = $callStack->moveToNextCorrespondingExitTag();
                $templateCallStackInstructions = $templateEndCall->getPluginData(FragmentTag::CALLSTACK);
                if ($templateCallStackInstructions !== null) {
                    $templateCallStack = CallStack::createFromInstructions($templateCallStackInstructions);
                    // Lazy load
                    $templateCallStack->moveToStart();
                    CarrouselTag::setLazyLoadToHtmlOnImageTagUntilTheEndOfTheStack($templateCallStack);
                    $templateEndCall->setPluginData(FragmentTag::CALLSTACK, $templateCallStack->getStack());
                }
            } else {
                // Lazy load
                $callStack->moveToEnd();
                $callStack->moveToPreviousCorrespondingOpeningCall();
                CarrouselTag::setLazyLoadToHtmlOnImageTagUntilTheEndOfTheStack($callStack);
            }
        }
        return array(PluginUtility::ATTRIBUTES => $openingCall->getAttributes());
    }

    public static function renderEnterXhtml(TagAttributes $tagAttributes, array $data): string
    {
        /**
         * Control
         */
        $control = $tagAttributes->getValueAndRemoveIfPresent(CarrouselTag::CONTROL_ATTRIBUTE);
        if ($control !== null) {
            $tagAttributes->addOutputAttributeValue("data-" . CarrouselTag::CONTROL_ATTRIBUTE, $control);
        }

        /**
         * Element Min
         */
        $elementsMin = $tagAttributes->getValueAndRemoveIfPresent(CarrouselTag::ELEMENTS_MIN_ATTRIBUTE, CarrouselTag::ELEMENTS_MIN_DEFAULT);
        $tagAttributes->addOutputAttributeValue("data-" . CarrouselTag::ELEMENTS_MIN_ATTRIBUTE, $elementsMin);

        /**
         * Minimal Width
         */
        $slideMinimalWidth = $tagAttributes->getValueAndRemoveIfPresent(CarrouselTag::ELEMENT_WIDTH_ATTRIBUTE);
        if ($slideMinimalWidth !== null) {
            try {
                $slideMinimalWidth = ConditionalLength::createFromString($slideMinimalWidth)->toPixelNumber();
                $tagAttributes->addOutputAttributeValue("data-" . CarrouselTag::ELEMENT_WIDTH_ATTRIBUTE, $slideMinimalWidth);
            } catch (ExceptionCompile $e) {
                LogUtility::msg("The minimal width value ($slideMinimalWidth) is not a valid value. Error: {$e->getMessage()}");
            }
        }


        /**
         * Snippets
         */
        $snippetSystem = ExecutionContext::getActualOrCreateFromEnv()
            ->getSnippetSystem();


        $snippetId = CarrouselTag::TAG;

        // Theme customized from the below official theme
        // https://cdn.jsdelivr.net/npm/@glidejs/glide@3.5.2/dist/css/glide.theme.css
        $snippetSystem->attachCssInternalStyleSheet($snippetId)->setCritical(false);

        /**
         * The dependency first
         */
        $snippetSystem->attachJavascriptFromComponentId("combo-loader");
        $snippetSystem->attachJavascriptFromComponentId($snippetId);

        /**
         * Return
         */
        return $tagAttributes->toHtmlEnterTag("div");
    }

    public static function renderExitXhtml(): string
    {
        return '</div>';
    }
}

