<?php

namespace ComboStrap;


use syntax_plugin_combo_label;
use syntax_plugin_combo_tab;

/**
 * The tabs component is a little bit a nasty one
 * because it's used in three cases:
 *   * the new syntax to enclose the panels
 *   * the new syntax to create the tabs
 *   * the old syntax to create the tabs
 * The code is using the context to manage this cases
 *
 * Full example can be found
 * in the Javascript section of tabs and navs
 * https://getbootstrap.com/docs/5.0/components/navs-tabs/#javascript-behavior
 *
 * Vertical Pills
 * https://getbootstrap.com/docs/4.0/components/navs/#vertical
 *
 * React: https://react.dev/reference/react/Children#calling-a-render-prop-to-customize-rendering
 */
class TabsTag
{


    /**
     * A tabs with this context will render the `ul` HTML tags
     * (ie tabs enclose the navigation partition)
     */
    public const NAVIGATION_CONTEXT = "navigation-context";
    public const PILLS_TYPE = "pills";
    public const TABS_SKIN = "tabs";
    public const ENCLOSED_TABS_TYPE = "enclosed-tabs";
    public const ENCLOSED_PILLS_TYPE = "enclosed-pills";
    public const LABEL = 'label';
    public const SELECTED_ATTRIBUTE = "selected";
    public const TAG = 'tabs';
    /**
     * A key attributes to set on in the instructions the attributes
     * of panel
     */
    public const KEY_PANEL_ATTRIBUTES = "panels";
    /**
     * Type tabs
     */
    public const TABS_TYPE = "tabs";
    public const PILLS_SKIN = "pills";

    public static function closeNavigationalHeaderComponent($type)
    {
        $html = "</ul>" . DOKU_LF;
        switch ($type) {
            case self::ENCLOSED_PILLS_TYPE:
            case self::ENCLOSED_TABS_TYPE:
                $html .= "</div>" . DOKU_LF;
        }
        return $html;

    }

    /**
     * @param $tagAttributes
     * @return string - the opening HTML code of the tab navigational header
     */
    public static function openNavigationalTabsElement(TagAttributes $tagAttributes): string
    {

        /**
         * Unset non-html attributes
         */
        $tagAttributes->removeComponentAttributeIfPresent(self::KEY_PANEL_ATTRIBUTES);

        /**
         * Type (Skin determination)
         */
        $type = self::getComponentType($tagAttributes);

        /**
         * $skin (tabs or pills)
         */
        $skin = self::TABS_TYPE;
        switch ($type) {
            case self::TABS_TYPE:
            case self::ENCLOSED_TABS_TYPE:
                $skin = self::TABS_SKIN;
                break;
            case self::PILLS_TYPE:
            case self::ENCLOSED_PILLS_TYPE:
                $skin = self::PILLS_SKIN;
                break;
            default:
                LogUtility::warning("The tabs type ($type) has an unknown skin", self::TAG);
        }

        /**
         * Creates the panel wrapper element
         */
        $html = "";
        switch ($type) {
            case self::TABS_TYPE:
            case self::PILLS_TYPE:
                if (!$tagAttributes->hasAttribute(Spacing::SPACING_ATTRIBUTE)) {
                    $tagAttributes->addComponentAttributeValue(Spacing::SPACING_ATTRIBUTE, "mb-3");
                }
                $tagAttributes->addClassName("nav")
                    ->addClassName("nav-$skin");
                $tagAttributes->addOutputAttributeValue('role', 'tablist');
                $html = $tagAttributes->toHtmlEnterTag("ul");
                break;
            case self::ENCLOSED_TABS_TYPE:
            case self::ENCLOSED_PILLS_TYPE:
                /**
                 * The HTML opening for cards
                 */
                $tagAttributes->addClassName("card");
                $html = $tagAttributes->toHtmlEnterTag("div") .
                    "<div class=\"card-header\">";
                /**
                 * The HTML opening for the menu (UL)
                 */
                $html .= TagAttributes::createEmpty()
                    ->addClassName("nav")
                    ->addClassName("nav-$skin")
                    ->addClassName("card-header-$skin")
                    ->toHtmlEnterTag("ul");
                break;
            default:
                LogUtility::error("The tabs type ($type) is unknown", self::TAG);
        }
        return $html;

    }

    /**
     * @param array $attributes
     * @return string
     */
    public static function openNavigationalTabElement(array $attributes): string
    {
        $liTagAttributes = TagAttributes::createFromCallStackArray($attributes);

        /**
         * Check all attributes for the link (not the li)
         * and delete them
         */
        $active = PanelTag::getSelectedValue($liTagAttributes);
        $panel = "";


        $panel = $liTagAttributes->getValueAndRemoveIfPresent("panel");
        if ($panel === null && $liTagAttributes->hasComponentAttribute("id")) {
            $panel = $liTagAttributes->getValueAndRemoveIfPresent("id");
        }
        if ($panel === null) {
            LogUtility::msg("A id attribute is missing on a panel tag", LogUtility::LVL_MSG_ERROR, TabsTag::TAG);
        }


        /**
         * Creating the li element
         */
        $html = $liTagAttributes->addClassName("nav-item")
            ->addOutputAttributeValue("role", "presentation")
            ->toHtmlEnterTag("li");

        /**
         * Creating the a element
         */
        $namespace = Bootstrap::getDataNamespace();
        $htmlAttributes = TagAttributes::createEmpty();
        if ($active === true) {
            $htmlAttributes->addClassName("active");
            $htmlAttributes->addOutputAttributeValue("aria-selected", "true");
        }
        $html .= $htmlAttributes
            ->addClassName("nav-link")
            ->addOutputAttributeValue('id', $panel . "-tab")
            ->addOutputAttributeValue("data{$namespace}-toggle", "tab")
            ->addOutputAttributeValue('aria-controls', $panel)
            ->addOutputAttributeValue("role", "tab")
            ->addOutputAttributeValue('href', "#$panel")
            ->toHtmlEnterTag("a");

        return $html;
    }

    public static function closeNavigationalTabElement(): string
    {
        return "</a></li>";
    }

    /**
     * @param TagAttributes $tagAttributes
     * @return string - return the HTML open tags of the panels (not the navigation)
     */
    public static function openTabPanelsElement(TagAttributes $tagAttributes): string
    {

        $tagAttributes->addClassName("tab-content");

        /**
         * In preview with only one panel
         */
        global $ACT;
        if ($ACT === "preview" && $tagAttributes->hasComponentAttribute(self::SELECTED_ATTRIBUTE)) {
            $tagAttributes->removeComponentAttribute(self::SELECTED_ATTRIBUTE);
        }

        $html = $tagAttributes->toHtmlEnterTag("div");
        $type = self::getComponentType($tagAttributes);
        switch ($type) {
            case self::ENCLOSED_TABS_TYPE:
            case self::ENCLOSED_PILLS_TYPE:
                $html = "<div class=\"card-body\">" . $html;
                break;
        }
        return $html;

    }

    public static function getComponentType(TagAttributes $tagAttributes)
    {

        $skin = $tagAttributes->getValueAndRemoveIfPresent("skin");
        if ($skin !== null) {
            return $skin;
        }

        $type = $tagAttributes->getType();
        if ($type !== null) {
            return $type;
        }
        return self::TABS_TYPE;
    }

    public static function closeTabPanelsElement(TagAttributes $tagAttributes): string
    {
        $html = "</div>";
        $type = self::getComponentType($tagAttributes);
        switch ($type) {
            case self::ENCLOSED_TABS_TYPE:
            case self::ENCLOSED_PILLS_TYPE:
                $html .= "</div>";
                $html .= "</div>";
                break;
        }
        return $html;
    }

    public static function handleExit($handler): array
    {
        $callStack = CallStack::createFromHandler($handler);
        $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
        if ($openingTag === false) {
            LogUtility::error("A tabs tag had no opening tag and was discarded");
            return array(
                PluginUtility::CONTEXT => "root",
                PluginUtility::ATTRIBUTES => []
            );
        }
        $previousOpeningTag = $callStack->previous();
        $callStack->next();
        $firstChild = $callStack->moveToFirstChildTag();
        $context = null;
        if ($firstChild !== false) {
            /**
             * Add the context to the opening and ending tag
             */
            $context = $firstChild->getTagName();
            $openingTag->setContext($context);
            /**
             * Does tabs enclosed Panel (new syntax)
             */
            if ($context == PanelTag::PANEL_LOGICAL_MARKUP) {

                /**
                 * We scan the tabs and derived:
                 * * the navigation tabs element (ie ul/li nav-tab)
                 * * the tab pane element
                 */

                /**
                 * This call will create the ul
                 * <ul class="nav nav-tabs mb-3" role="tablist">
                 * thanks to the {@link TabsTag::NAVIGATION_CONTEXT}
                 */
                $navigationalCalls[] = Call::createComboCall(
                    TabsTag::TAG,
                    DOKU_LEXER_ENTER,
                    $openingTag->getAttributes(),
                    TabsTag::NAVIGATION_CONTEXT,
                    null,
                    null,
                    null,
                    \syntax_plugin_combo_xmlblocktag::TAG
                );

                /**
                 * The tab pane elements
                 */
                $tabPaneCalls = [$openingTag, $firstChild];

                /**
                 * Copy the stack
                 */
                $labelState = "label";
                $nonLabelState = "non-label";
                $scanningState = $nonLabelState;
                while ($actual = $callStack->next()) {

                    if (
                        $actual->getTagName() == syntax_plugin_combo_label::TAG
                        &&
                        $actual->getState() == DOKU_LEXER_ENTER
                    ) {
                        $scanningState = $labelState;
                    }

                    if ($labelState === $scanningState) {
                        $navigationalCalls[] = $actual;
                    } else {
                        $tabPaneCalls[] = $actual;
                    }

                    if (
                        $actual->getTagName() == syntax_plugin_combo_label::TAG
                        &&
                        $actual->getState() == DOKU_LEXER_EXIT
                    ) {
                        $scanningState = $nonLabelState;
                    }


                }

                /**
                 * End navigational tabs
                 */
                $navigationalCalls[] = Call::createComboCall(
                    TabsTag::TAG,
                    DOKU_LEXER_EXIT,
                    $openingTag->getAttributes(),
                    TabsTag::NAVIGATION_CONTEXT,
                    null,
                    null,
                    null,
                    \syntax_plugin_combo_xmlblocktag::TAG
                );

                /**
                 * Rebuild
                 */
                $callStack->deleteAllCallsAfter($previousOpeningTag);
                $callStack->appendCallsAtTheEnd($navigationalCalls);
                $callStack->appendCallsAtTheEnd($tabPaneCalls);

            }
        }

        return array(
            PluginUtility::CONTEXT => $context,
            PluginUtility::ATTRIBUTES => $openingTag->getAttributes()
        );
    }

    public static function renderEnterXhtml(TagAttributes $tagAttributes, array $data): string
    {
        $context = $data[PluginUtility::CONTEXT];
        switch ($context) {
            /**
             * When the tag tabs enclosed the panels
             */
            case PanelTag::PANEL_LOGICAL_MARKUP:
                return TabsTag::openTabPanelsElement($tagAttributes);
            /**
             * When the tag tabs are derived (new syntax)
             */
            case TabsTag::NAVIGATION_CONTEXT:
                /**
                 * Old syntax, when the tag had to be added specifically
                 */
            case syntax_plugin_combo_tab::TAG:
                return TabsTag::openNavigationalTabsElement($tagAttributes);
            default:
                LogUtility::internalError("The context ($context) is unknown in enter", TabsTag::TAG);
                return "";

        }
    }

    public static function renderExitXhtml(TagAttributes $tagAttributes, array $data): string
    {
        $context = $data[PluginUtility::CONTEXT];
        switch ($context) {
            /**
             * New syntax (tabpanel enclosing)
             */
            case PanelTag::PANEL_LOGICAL_MARKUP:
                return TabsTag::closeTabPanelsElement($tagAttributes);
            /**
             * Old syntax
             */
            case syntax_plugin_combo_tab::TAG:
                /**
                 * New syntax (Derived)
                 */
            case TabsTag::NAVIGATION_CONTEXT:
                $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                $type = TabsTag::getComponentType($tagAttributes);
                return TabsTag::closeNavigationalHeaderComponent($type);
            default:
                LogUtility::log2FrontEnd("The context $context is unknown in exit", LogUtility::LVL_MSG_ERROR, TabsTag::TAG);
                return "";
        }
    }
}

