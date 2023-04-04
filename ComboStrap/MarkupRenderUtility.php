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
     * @throws ExceptionCompile
     */
    public static function renderText2XhtmlAndStripPEventually($content, bool $strip = true): string
    {

        return FetcherMarkup::confRoot()
            ->setRequestedMarkupString($content)
            ->setDeleteRootBlockElement($strip)
            ->setRequestedContextPathWithDefault()
            ->setRequestedMimeToXhtml()
            ->setIsStandAloneCodeExecution(true)
            ->build()
            ->getFetchString();

    }

    /**
     * @param $pageContent - the text (not the id)
     * @param bool $stripOpenAndEnd - to avoid the p element in test rendering
     * @return array
     */
    public static function getInstructionsAndStripPEventually($pageContent, bool $stripOpenAndEnd = true): array
    {

        $markupRenderer = FetcherMarkup::confRoot()
            ->setDeleteRootBlockElement($stripOpenAndEnd)
            ->setRequestedMarkupString($pageContent)
            ->setRequestedMimeToInstructions()
            ->setRequestedContextPathWithDefault()
            ->build();

        return $markupRenderer->getInstructions();


    }

    /**
     * @param $pageId
     * @return string
     * @throws ExceptionCompile
     */
    public
    static function renderId2Xhtml($pageId): string
    {

        $wikiPath = WikiPath::createMarkupPathFromId($pageId);
        $fetcher = FetcherMarkup::createXhtmlMarkupFetcherFromPath($wikiPath, $wikiPath);
        return $fetcher->getFetchString();


    }

    /**
     * @param $callStackHeaderInstructions
     * @param $contextData - the context data if any
     * @return string|null
     * @throws ExceptionCompile
     */
    public static function renderInstructionsToXhtml($callStackHeaderInstructions, array $contextData = null): string
    {


        $builder = FetcherMarkup::confChild()
            ->setRequestedInstructions($callStackHeaderInstructions)
            ->setIsDocument(false)
            ->setRequestedMimeToXhtml()
            ->setRequestedContextPathWithDefault();
        if ($contextData !== null) {
            $builder->setContextData($contextData);
        }
        return $builder->build()
            ->getFetchString();
    }

    /**
     * @throws ExceptionCompile
     */
    public static function renderInstructionsToXhtmlFromPage($callStackHeaderInstructions, MarkupPath $renderingPageId): string
    {
        return self::renderInstructionsToXhtml($callStackHeaderInstructions, $renderingPageId->getMetadataForRendering());
    }


}
