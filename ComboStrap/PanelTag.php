<?php

namespace ComboStrap;


use Doku_Handler;
use syntax_plugin_combo_accordion;
use syntax_plugin_combo_label;
use syntax_plugin_combo_tab;

/**
 * A panel wrap content that is cached
 * via tabs or accordon
 */
class PanelTag
{


    public const SELECTED = 'selected';
    public const PANEL_MARKUP = 'panel';
    public const CANONICAL = PanelTag::PANEL_LOGICAL_MARKUP;
    public const PANEL_LOGICAL_MARKUP = 'panel';
    public const STATE = 'state';
    public const TAB_PANEL_MARKUP = 'tabpanel';
    public const CONTEXT_PREVIEW_ALONE_ATTRIBUTES = array(
        PanelTag::SELECTED => true,
        TagAttributes::ID_KEY => "alone",
        TagAttributes::TYPE_KEY => TabsTag::ENCLOSED_TABS_TYPE
    );
    public const CONF_ENABLE_SECTION_EDITING = "panelEnableSectionEditing";
    /**
     * When the panel is alone in the edit due to the sectioning
     */
    public const CONTEXT_PREVIEW_ALONE = "preview_alone";

    public static function getSelectedValue(TagAttributes $tagAttributes)
    {
        $selected = $tagAttributes->getValueAndRemoveIfPresent(PanelTag::SELECTED);
        if ($selected !== null) {
            /**
             * Value may be false/true
             */
            return DataType::toBoolean($selected);

        }
        if ($tagAttributes->hasComponentAttribute(TagAttributes::TYPE_KEY)) {
            $type = $tagAttributes->getType();
            if (strtolower($type) === "selected") {
                return true;
            }
        }
        return false;

    }


    public static function handleEnter(TagAttributes $tagAttributes, Doku_Handler $handler, string $markupTag): array
    {

        $callStack = CallStack::createFromHandler($handler);
        $parent = $callStack->moveToParent();
        if ($parent !== false) {
            $context = $parent->getTagName();
        } else {
            /**
             * The panel may be alone in preview
             * due to the section edit button
             */
            global $ACT;
            if ($ACT == "preview") {
                $context = PanelTag::CONTEXT_PREVIEW_ALONE;
            } else {
                // to be able to see the old markup
                $context = $markupTag;
            }
        }

        $idManager = ExecutionContext::getActualOrCreateFromEnv()->getIdManager();

        try {
            $id = $tagAttributes->getId();
        } catch (ExceptionNotFound $e) {
            $id = $idManager->generateNewHtmlIdForComponent(self::PANEL_LOGICAL_MARKUP . "-" . $context);
            $tagAttributes->setId($id);
        }

        /**
         * Old deprecated syntax
         */
        if ($markupTag == PanelTag::TAB_PANEL_MARKUP) {

            $context = PanelTag::TAB_PANEL_MARKUP;

            $siblingTag = $callStack->moveToPreviousSiblingTag();
            if ($siblingTag != null) {
                if ($siblingTag->getTagName() === TabsTag::TAG) {
                    $tagAttributes->setComponentAttributeValue(PanelTag::SELECTED, false);
                    while ($descendant = $callStack->next()) {
                        $descendantName = $descendant->getTagName();
                        $descendantPanel = $descendant->getAttribute("panel");
                        $descendantSelected = $descendant->getAttribute(PanelTag::SELECTED);
                        if (
                            $descendantName == syntax_plugin_combo_tab::TAG
                            && $descendantPanel === $id
                            && $descendantSelected === "true") {
                            $tagAttributes->setComponentAttributeValue(PanelTag::SELECTED, true);
                            break;
                        }
                    }
                } else {
                    LogUtility::msg("The direct element above a " . PanelTag::TAB_PANEL_MARKUP . " should be a `tabs` and not a `" . $siblingTag->getTagName() . "`", LogUtility::LVL_MSG_ERROR, "tabs");
                }
            }
        }

        $id = $idManager->generateNewHtmlIdForComponent(PanelTag::PANEL_LOGICAL_MARKUP);
        return array(
            PluginUtility::CONTEXT => $context,
            TagAttributes::ID_KEY => $id
        );

    }

    public static function handleExit(Doku_Handler $handler, int $pos, string $markupTag, string $match): array
    {

        $callStack = CallStack::createFromHandler($handler);
        $openingTag = $callStack->moveToPreviousCorrespondingOpeningCall();
        if ($openingTag === false) {
            LogUtility::error("An exit panel tag does not have any opening tag and was discarded");
            return [PluginUtility::CONTEXT => "root"];
        }

        /**
         * Label is Mandatory in the new syntax. We check it
         * (Only the presence of at minimum 1 and not the presence in each panel)
         */
        if ($markupTag !== PanelTag::TAB_PANEL_MARKUP) {
            $labelCall = null;
            while ($actualCall = $callStack->next()) {
                if ($actualCall->getTagName() === syntax_plugin_combo_label::TAG) {
                    $labelCall = $actualCall;
                    break;
                }
            }
            if ($labelCall === null) {
                LogUtility::error("No label was found in the panel (number " . $openingTag->getIdOrDefault() . "). They are mandatory to create tabs or accordion", PanelTag::PANEL_LOGICAL_MARKUP);
            }
        }


        /**
         * End section
         */
        $sectionEditing = ExecutionContext::getActualOrCreateFromEnv()
            ->getConfig()
            ->getValue(PanelTag::CONF_ENABLE_SECTION_EDITING, 1);
        if ($sectionEditing) {
            /**
             * Section
             * +1 to go at the line
             */
            $startPosition = $openingTag->getPluginData(PluginUtility::POSITION);
            $id = $openingTag->getAttribute(TagAttributes::ID_KEY);
            $endPosition = $pos + strlen($match) + 1;
            $editButtonCall = EditButton::create("Edit panel $id")
                ->setStartPosition($startPosition)
                ->setEndPosition($endPosition)
                ->toComboCallComboFormat();
            $callStack->moveToEnd();
            $callStack->insertBefore($editButtonCall);
        }

        /**
         * Add p
         */
        $callStack->moveToEnd();
        $callStack->moveToPreviousCorrespondingOpeningCall();
        $callStack->processEolToEndStack();
        return array(PluginUtility::CONTEXT => $openingTag->getContext());
    }

    public static function renderEnterXhtml(TagAttributes $tagAttributes, array $data): string
    {
        /**
         * Section (Edit button)
         */
        if (SiteConfig::getConfValue(PanelTag::CONF_ENABLE_SECTION_EDITING, 1)) {
            $position = $data[PluginUtility::POSITION];
            $name = IdManager::getOrCreate()->generateNewHtmlIdForComponent(PanelTag::PANEL_LOGICAL_MARKUP);
            EditButtonManager::getOrCreate()->createAndAddEditButtonToStack($name, $position);
        }

        $context = $data[PluginUtility::CONTEXT];
        switch ($context) {
            case syntax_plugin_combo_accordion::TAG:
                // A panel in a accordion
                return "<div class=\"card\">";
            case PanelTag::TAB_PANEL_MARKUP: // Old deprecated syntax
            case TabsTag::TAG: // new syntax


                try {
                    $ariaLabelledValue = $tagAttributes->getId() . "-tab";
                } catch (ExceptionNotFound $e) {
                    LogUtility::error("No id was found for a panel in the tabs");
                    $ariaLabelledValue = "unknwon-id-tab";
                }
                $tagAttributes
                    ->addClassName("tab-pane fade")
                    ->addOutputAttributeValue("role", "tabpanel")
                    ->addOutputAttributeValue("aria-labelledby", $ariaLabelledValue);
                $selected = PanelTag::getSelectedValue($tagAttributes);
                if ($selected) {
                    $tagAttributes->addClassName("show active");
                }
                return $tagAttributes->toHtmlEnterTag("div");

            case PanelTag::CONTEXT_PREVIEW_ALONE:
                $aloneAttributes = TagAttributes::createFromCallStackArray(PanelTag::CONTEXT_PREVIEW_ALONE_ATTRIBUTES);
                return TabsTag::openTabPanelsElement($aloneAttributes);
            default:
                LogUtility::log2FrontEnd("The context ($context) is unknown in enter rendering", LogUtility::LVL_MSG_ERROR, PanelTag::PANEL_LOGICAL_MARKUP);
                return "";
        }
    }

    public static function renderExitXhtml(array $data): string
    {

        $xhtml = "";
        $context = $data[PluginUtility::CONTEXT];
        switch ($context) {
            case syntax_plugin_combo_accordion::TAG:
                $xhtml .= '</div></div>';
                break;
            case PanelTag::CONTEXT_PREVIEW_ALONE:
                $aloneVariable = TagAttributes::createFromCallStackArray(PanelTag::CONTEXT_PREVIEW_ALONE_ATTRIBUTES);
                $xhtml .= TabsTag::closeTabPanelsElement($aloneVariable);
                break;
        }

        /**
         * End panel
         */
        $xhtml .= "</div>";
        return $xhtml;
    }
}

