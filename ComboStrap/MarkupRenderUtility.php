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


use dokuwiki\Extension\SyntaxPlugin;
use PHPUnit\Exception;

class MarkupRenderUtility
{

    /**
     * @param $content
     * @param bool $strip
     * @return string
     */
    public static function renderText2XhtmlAndStripPEventually($content, bool $strip = true): string
    {

        $markupRenderer = MarkupRenderer::createFromMarkup($content)
            ->setDeleteRootBlockElement($strip)
            ->setRequestedMimeToXhtml();


        return $markupRenderer->getOutput();


    }

    /**
     * @param $pageContent - the text (not the id)
     * @param bool $stripOpenAndEnd - to avoid the p element in test rendering
     * @return array
     */
    public static function getInstructionsAndStripPEventually($pageContent, bool $stripOpenAndEnd = true): array
    {

        $markupRenderer = MarkupRenderer::createFromMarkup($pageContent)
            ->setRequestedMimeToInstruction()
            ->setDeleteRootBlockElement($stripOpenAndEnd);

        return $markupRenderer->getOutput();


    }

    /**
     * @param $pageId
     * @return string
     */
    public
    static function renderId2Xhtml($pageId): string
    {
        $wikiPath = WikiPath::createPagePathFromId($pageId);
        $fetcher = FetcherMarkup::createPageFragmentFetcherFromPath($wikiPath)
            ->setRemoveRootBlockElement(true)
            ->setRequestedMimeToXhtml();
        try {
            return $fetcher->getFetchString();
        } finally {
            $fetcher->close();
        }

    }

    /**
     * @param $callStackHeaderInstructions
     * @param $contextData - the page id used to render this instructions (it's not the global ID that represents the document, inside a document, for a dynamic component, you may loop through pages, this is the page id of the loop)
     * @return string|null
     * @throws ExceptionCompile
     */
    public static function renderInstructionsToXhtml($callStackHeaderInstructions, array $contextData = null): string
    {
        global $ID;
        $keepID = $ID;
        global $ACT;
        $keepACT = $ACT;
        global $ID;
        $contextManager = ContextManager::getOrCreate();
        if ($contextData !== null) {
            $contextManager->setContextArrayData($contextData);
        }
        try {

            if ($ID === null && PluginUtility::isTest()) {
                $ID = ExecutionContext::DEFAULT_SLOT_ID_FOR_TEST;
            }
            $ACT = MarkupDynamicRender::DYNAMIC_RENDERING;
            $output = p_render("xhtml", $callStackHeaderInstructions, $info);
            if ($output === null) {
                throw new ExceptionBadState("The rendering output was null");
            }
            return $output;
        } catch (Exception $e) {
            /**
             * Example of errors;
             * method_exists() expects parameter 2 to be string, array given
             * inc\parserutils.php:672
             */
            throw new ExceptionCompile("Error while rendering instructions. Error was: {$e->getMessage()}");
        } finally {
            $ACT = $keepACT;
            $ID = $keepID;
            $contextManager->reset();
        }
    }

    /**
     * @throws ExceptionCompile
     */
    public static function renderInstructionsToXhtmlFromPage($callStackHeaderInstructions, MarkupPath $renderingPageId): string
    {
        return self::renderInstructionsToXhtml($callStackHeaderInstructions, $renderingPageId->getMetadataForRendering());
    }


}
