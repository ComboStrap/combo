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

class RenderUtility
{

    /**
     * @param $content
     * @param bool $strip
     * @return string|null
     */
    public static function renderText2XhtmlAndStripPEventually($content, bool $strip = true): ?string
    {
        global $ID;
        $keep = $ID;
        global $ACT;
        $keepAct = $ACT;
        $ACT = DynamicRender::DYNAMIC_RENDERING;
        if ($ID === null && PluginUtility::isTest()) {
            $ID = DynamicRender::DEFAULT_SLOT_ID_FOR_TEST;
        }
        try {
            $instructions = self::getInstructionsAndStripPEventually($content, $strip);
            return p_render('xhtml', $instructions, $info);
        } finally {
            $ID = $keep;
            $ACT = $keepAct;
        }

    }

    /**
     * @param $pageContent - the text (not the id)
     * @param bool $stripOpenAndEnd - to avoid the p element in test rendering
     * @return array
     */
    public static function getInstructionsAndStripPEventually($pageContent, bool $stripOpenAndEnd = true): array
    {
        global $ID;
        $keepID = $ID;
        global $ACT;
        $keepACT = $ACT;
        global $ID;
        try {
            if ($ID === null && PluginUtility::isTest()) {
                $ID = DynamicRender::DEFAULT_SLOT_ID_FOR_TEST;
            }
            $ACT = DynamicRender::DYNAMIC_RENDERING;
            $instructions = p_get_instructions($pageContent);
        } finally {
            $ACT = $keepACT;
            $ID = $keepID;
        }

        if ($stripOpenAndEnd) {

            /**
             * Delete the p added by {@link Block::process()}
             * if the plugin of the {@link SyntaxPlugin::getPType() normal} and not in a block
             *
             * p_open = document_start in renderer
             */
            if ($instructions[1][0] == 'p_open') {
                unset($instructions[1]);

                /**
                 * The last p position is not fix
                 * We may have other calls due for instance
                 * of {@link \action_plugin_combo_syntaxanalytics}
                 */
                $n = 1;
                while (($lastPBlockPosition = (sizeof($instructions) - $n)) >= 0) {

                    /**
                     * p_open = document_end in renderer
                     */
                    if ($instructions[$lastPBlockPosition][0] == 'p_close') {
                        unset($instructions[$lastPBlockPosition]);
                        break;
                    } else {
                        $n = $n + 1;
                    }
                }
            }

        }

        return $instructions;
    }

    /**
     * @param $pageId
     * @return string|null
     */
    public
    static function renderId2Xhtml($pageId)
    {
        $file = wikiFN($pageId);
        if (file_exists($file)) {
            global $ID;
            $keep = $ID;
            $ID = $pageId;
            $content = file_get_contents($file);
            $xhtml = self::renderText2XhtmlAndStripPEventually($content);
            $ID = $keep;
            return $xhtml;
        } else {
            return false;
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
                $ID = DynamicRender::DEFAULT_SLOT_ID_FOR_TEST;
            }
            $ACT = DynamicRender::DYNAMIC_RENDERING;
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
    public static function renderInstructionsToXhtmlFromPage($callStackHeaderInstructions, PageFragment $renderingPageId): string
    {
        return self::renderInstructionsToXhtml($callStackHeaderInstructions, $renderingPageId->getMetadataForRendering());
    }


}
