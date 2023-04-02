<?php

namespace ComboStrap;

class SlotSystem
{

    const CANONICAL = "slot-systen";

    /**
     * A slot should never run in its own path as context path
     * This function returns the context path in which see should run
     * (ie the last visited path)
     *
     * This context path is given globally via the {@link ExecutionContext::getContextPath()}
     * when the {@link MarkupPath::isSlot() page is a slot}
     * @throws ExceptionNotFound
     */
    public static function getContextPath(): MarkupPath
    {
        $crumbs = $_SESSION[DOKU_COOKIE]['bc'] ?? array();
        if (empty($crumbs)) {
            throw new ExceptionNotFound("No historical crumbs");
        }
        $size = sizeof($crumbs);
        $visitedIds = array_keys($crumbs);
        for ($i = $size - 1; $i > 0; $i--) {
            $id = $visitedIds[$i];
            $markupPath = MarkupPath::createMarkupFromId($id);
            if (!$markupPath->isSlot()) {
                return $markupPath;
            }
        }
        throw new ExceptionNotFound("No historical crumbs");
    }

    public static function sendContextPathMessage(MarkupPath $contextPath): void
    {
        try {
            $anchorLink = LinkMarkup::createFromPageIdOrPath($contextPath->getWikiId())
                    ->toAttributes()
                    ->toHtmlEnterTag("a")
                . $contextPath->getTitleOrDefault()
                . "</a>";
        } catch (ExceptionBadArgument|ExceptionNotFound $e) {
            LogUtility::internalError("Should not happen");
            $anchorLink = $contextPath->getTitleOrDefault();
        }
        $docLink = PluginUtility::getDocumentationHyperLink("slot", "slot");

        $html = <<<EOF
  <p>This page is a $docLink.</p>
   <p>It returns information as if it was run within the last visited path: $anchorLink</p>
EOF;
        LogUtility::warning($html, self::CANONICAL);

    }

    public static function getSlotNames(): array
    {

        try {
            return [
                self::getSidebarName(),
                self::getPageHeaderSlotName(),
                self::getPageFooterSlotName(),
                self::getMainHeaderSlotName(),
                self::getMainFooterSlotName(),
                self::getMainSideSlotName()
            ];
        } catch (ExceptionCompile $e) {
            LogUtility::msg("An error has occurred while retrieving the name of the secondary slots. Error: {$e->getMessage()}");
            // We known at least this one
            return [
                self::getSidebarName(),
                self::getMainHeaderSlotName(),
                self::getMainFooterSlotName()
            ];
        }


    }

    /**
     *
     */
    public static function getMainHeaderSlotName(): ?string
    {
        return Site::SLOT_MAIN_HEADER_NAME;
    }

    /**
     */
    public static function getPageFooterSlotName()
    {
        return ExecutionContext::getActualOrCreateFromEnv()->getConfValue(TemplateSlot::CONF_PAGE_FOOTER_NAME, TemplateSlot::CONF_PAGE_FOOTER_NAME_DEFAULT);
    }

    /**
     * @deprecated
     */
    public static function getPageHeaderSlotName()
    {
        return ExecutionContext::getActualOrCreateFromEnv()->getConfig()->getPageHeaderSlotName();
    }

    /**
     * @deprecated
     */
    public static function getMainSideSlotName()
    {
        return ExecutionContext::getActualOrCreateFromEnv()->getConfValue(TemplateSlot::CONF_PAGE_MAIN_SIDEKICK_NAME, TemplateSlot::CONF_PAGE_MAIN_SIDEKICK_NAME_DEFAULT);
    }

    /**
     *
     */
    public static function getMainFooterSlotName(): string
    {
        return Site::SLOT_MAIN_FOOTER_NAME;
    }

    /**
     * @return string - the name of the sidebar page
     */
    public static function getSidebarName(): string
    {
        global $conf;
        return $conf["sidebar"];
    }
}
