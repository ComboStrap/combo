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

use ComboStrap\Align;
use ComboStrap\Bootstrap;
use ComboStrap\Call;
use ComboStrap\CallStack;
use ComboStrap\ConditionalLength;
use ComboStrap\DataType;
use ComboStrap\Dimension;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) {
    die();
}

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * The implementation of row/col system of Boostrap
 *
 *
 *
 */
class syntax_plugin_combo_grid extends DokuWiki_Syntax_Plugin
{

    const TAG = "grid";
    const TAGS = [self::TAG, self::TAG_OLD];
    const TAG_OLD = "row";

    /**
     * The strap template permits to
     * change this value
     * but because of the new grid system, it has been deprecated
     * We therefore don't get the grid total columns value from strap
     * @see {@link https://combostrap.com/dynamic_grid Dynamic Grid }
     */
    const GRID_TOTAL_COLUMNS = 12;


    const CANONICAL = self::TAG;

    /**
     * By default, div but used in a ul, it could be a li
     * This is modified in the callstack by the other component
     * @deprecated with the new {@link Align} (30/04/2022)
     */
    const HTML_TAG_ATT = "html-tag";

    /**
     *
     * @deprecated - contained/fit type was the same and has been deprecated for the {@link Align} attribute and the width value (grow/shrink)
     * (30/04/2022)
     */
    const TYPE_FIT_VALUE = "fit";

    /**
     * Used when the grid is not contained
     * and is just below the root
     * We set a value
     * @deprecated (30/04/2022)
     */
    const TYPE_AUTO_VALUE_DEPRECATED = "auto";
    /**
     * @deprecated (30/04/2022)
     */
    const TYPE_FIT_OLD_VALUE = "natural";

    const TYPE_WIDTH_SPECIFIED = "width";
    const KNOWN_TYPES = [self::TYPE_MAX_CHILDREN, self::TYPE_WIDTH_SPECIFIED, self::TYPE_AUTO_VALUE_DEPRECATED, self::TYPE_FIT_VALUE, self::TYPE_FIT_OLD_VALUE];
    const MAX_CHILDREN_ATTRIBUTE = "max-line";
    const TYPE_MAX_CHILDREN = "max";
    const GUTTER = "gutter";


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType(): string
    {
        return 'container';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     * All
     */
    public function getAllowedTypes(): array
    {
        return array('container', 'substition', 'protected', 'disabled', 'paragraphs');
    }


    public function accepts($mode): bool
    {

        return syntax_plugin_combo_preformatted::disablePreformatted($mode);

    }


    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType(): string
    {
        return 'stack';
    }

    /**
     * @see Doku_Parser_Mode::getSort()
     *
     * the mode with the lowest sort number will win out
     * the container (parent) must then have a lower number than the child
     */
    function getSort(): int
    {
        return 100;
    }

    /**
     * Create a pattern that will called this plugin
     *
     * @param string $mode
     * @see Doku_Parser_Mode::connectTo()
     */
    function connectTo($mode)
    {

        foreach (self::TAGS as $tag) {
            $pattern = PluginUtility::getContainerTagPattern($tag);
            $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));
        }


    }

    public function postConnect()
    {

        foreach (self::TAGS as $tag) {
            $this->Lexer->addExitPattern('</' . $tag . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));
        }


    }

    /**
     *
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        switch ($state) {

            case DOKU_LEXER_ENTER:

                $knownTypes = self::KNOWN_TYPES;
                /**
                 * All element are centered
                 * If their is 5 cells and the last one
                 * is going at the line, it will be centered
                 */
                $defaultAlign = "x-center-children";
                /**
                 * Vertical gutter
                 * On a two cell grid, the content will not
                 * touch on a mobile
                 *
                 * https://getbootstrap.com/docs/4.3/layout/grid/#no-gutters
                 * $attributes->addClassName("no-gutters");
                 */
                $defaultGutter = "y-5";
                $defaultAttributes = [
                    Align::ALIGN_ATTRIBUTE => $defaultAlign,
                    self::GUTTER => $defaultGutter
                ];
                $attributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes);

                $rowMatchPrefix = "<row";
                $isRowTag = substr($match, 0, strlen($rowMatchPrefix)) == $rowMatchPrefix;
                if ($isRowTag) {
                    LogUtility::warning("row has been deprecated for grid. You should rename the <row> tag with <grid>");
                }


                /**
                 * The deprecations
                 */
                $type = $attributes->getType();
                if (($type === self::TYPE_AUTO_VALUE_DEPRECATED)) {
                    LogUtility::warning("The auto rows type has been deprecated.", self::CANONICAL);
                    $attributes->removeType();
                }
                if ($type === self::TYPE_FIT_OLD_VALUE || $type === self::TYPE_FIT_VALUE) {
                    // in case it's the old value
                    $attributes->setType(self::TYPE_FIT_VALUE);
                    LogUtility::warning("Deprecation: The type value (" . self::TYPE_FIT_VALUE . " and " . self::TYPE_FIT_OLD_VALUE . ") for the align attribute and/or the grow/shrink width value with a box.", self::CANONICAL);
                }


                $callStack = CallStack::createFromHandler($handler);
                $parent = $callStack->moveToParent();


                /**
                 * Context
                 *   To add or not a margin-bottom,
                 *   To delete the image link or not
                 */
                if ($parent != false
                    && !in_array($parent->getTagName(), [
                        syntax_plugin_combo_bar::TAG,
                        syntax_plugin_combo_container::TAG,
                        syntax_plugin_combo_cell::TAG,
                        syntax_plugin_combo_iterator::TAG
                    ])
                    && $isRowTag
                ) {
                    $attributes->setType(self::TYPE_FIT_VALUE);
                    LogUtility::warning("The old row tag was used inside a component. We have deleted the grid layout. Rename your tag to a `grid` or `box` to delete this warning", self::CANONICAL);
                }


                $attributes->addComponentAttributeValue(self::HTML_TAG_ATT, "div");


                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes->toCallStackArray()
                );

            case DOKU_LEXER_UNMATCHED:
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);

                /**
                 * The returned array
                 * (filed while processing)
                 */
                $returnArray = array(PluginUtility::STATE => $state);

                /**
                 * Sizing Type mode determination
                 */
                $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
                $type = $openingCall->getType();

                /**
                 * Max-Cells Type ?
                 */
                $maxCells = null; // variable declaration to not have a linter warning
                /**
                 * @var ConditionalLength[] $maxCellsArray
                 */
                $maxCellsArray = []; // variable declaration to not have a linter warning
                if ($type == null) {

                    $maxCells = $openingCall->getAttribute(self::MAX_CHILDREN_ATTRIBUTE);
                    if ($maxCells !== null) {

                        $maxCellsValues = explode(" ", $maxCells);
                        foreach ($maxCellsValues as $maxCellsValue) {
                            try {
                                $maxCellLength = ConditionalLength::createFromString($maxCellsValue);
                            } catch (ExceptionBadArgument $e) {
                                LogUtility::error("The max-cells attribute value ($maxCellsValue) is not a valid length value. Error: {$e->getMessage()}", self::CANONICAL);
                                continue;
                            }
                            $number = $maxCellLength->getNumerator();
                            if ($number > 12) {
                                LogUtility::error("The max-cells attribute value ($maxCellsValue) should be less than 12.", self::CANONICAL);
                            }
                            $maxCellsArray[$maxCellLength->getBreakpointOrDefault()] = $maxCellLength;
                        }
                        $openingCall->removeAttribute(self::MAX_CHILDREN_ATTRIBUTE);
                        $type = self::TYPE_MAX_CHILDREN;
                    }
                }


                /**
                 * Gather the cells children
                 * Is there a template callstack
                 */
                $firstChildTag = $callStack->moveToFirstChildTag();
                $childrenOpeningTags = [];

                $templateEndTag = null; // the template end tag that has the instructions
                $callStackTemplate = null; // the instructions in callstack form to modify the children
                if ($firstChildTag->getTagName() === syntax_plugin_combo_template::TAG && $firstChildTag->getState() === DOKU_LEXER_ENTER) {
                    $templateEndTag = $callStack->next();
                    if ($templateEndTag->getTagName() !== syntax_plugin_combo_template::TAG || $templateEndTag->getState() !== DOKU_LEXER_EXIT) {
                        LogUtility::error("Error internal: We were unable to find the closing template tag.", self::CANONICAL);
                        return $returnArray;
                    }
                    $templateInstructions = $templateEndTag->getPluginData(syntax_plugin_combo_template::CALLSTACK);
                    $callStackTemplate = CallStack::createFromInstructions($templateInstructions);
                    $callStackTemplate->moveToStart();
                    $firstChildTag = $callStackTemplate->moveToFirstChildTag();
                    if ($firstChildTag !== false) {
                        $childrenOpeningTags[] = $firstChildTag;
                        while ($actualCall = $callStackTemplate->moveToNextSiblingTag()) {
                            $childrenOpeningTags[] = $actualCall;
                        }
                    }

                } else {

                    $childrenOpeningTags[] = $firstChildTag;
                    while ($actualCall = $callStack->moveToNextSiblingTag()) {
                        $childrenOpeningTags[] = $actualCall;
                    }

                }

                /**
                 * Scan and process the children
                 * - Add the col class
                 * - Do the cells have a width set ...
                 */
                foreach ($childrenOpeningTags as $actualCall) {
                    if ($type !== self::TYPE_FIT_VALUE) {
                        $actualCall->addClassName("col");
                    }
                    $childrenOpeningTags[] = $actualCall;
                    $widthAttributeValue = $actualCall->getAttribute(Dimension::WIDTH_KEY);
                    if ($widthAttributeValue !== null) {
                        $type = self::TYPE_WIDTH_SPECIFIED;
                        $conditionalWidthsLengths = explode(" ", $widthAttributeValue);
                        foreach ($conditionalWidthsLengths as $conditionalWidthsLength) {
                            try {
                                $conditionalLengthObject = ConditionalLength::createFromString($conditionalWidthsLength);
                            } catch (ExceptionBadArgument $e) {
                                $type = null;
                                LogUtility::error("The width length $conditionalWidthsLength is not a valid length value. Error: {$e->getMessage()}");
                                break;
                            }
                            try {
                                $ratio = $conditionalLengthObject->getRatio();
                                if ($ratio > 1) {
                                    $type = null;
                                    LogUtility::error("The ratio ($ratio) of the width ($conditionalLengthObject) should not be greater than 1 on the children of the row", self::CANONICAL);
                                    break;
                                }
                            } catch (ExceptionBadArgument $e) {
                                $type = null;
                                LogUtility::error("The ratio of the width ($conditionalLengthObject) is not a valid. Error: {$e->getMessage()}");
                                break;
                            }
                        }
                    }

                }

                if ($type === null) {
                    $type = self::TYPE_MAX_CHILDREN;
                }
                /**
                 * Setting the type on the opening tag to see the chosen type in the html attribute
                 */
                $openingCall->setType($type);


                /**
                 * Type is now known
                 * Do the Distribution calculation
                 */
                switch ($type) {
                    case self::TYPE_MAX_CHILDREN:
                        $maxCellDefaults = [];
                        try {
                            $maxCellDefaults["xs"] = ConditionalLength::createFromString("1-xs");
                            $maxCellDefaults["sm"] = ConditionalLength::createFromString("2-sm");
                            $maxCellDefaults["md"] = ConditionalLength::createFromString("3-md");
                            $maxCellDefaults["lg"] = ConditionalLength::createFromString("4-lg");
                        } catch (ExceptionBadArgument $e) {
                            LogUtility::error("Bad default value initialization. Error:{$e->getMessage()}", self::CANONICAL);
                        }
                        // Delete the default that are bigger than the asked max-cells number
                        $maxCellDefaultsFiltered = [];
                        if ($maxCells !== null) {
                            foreach ($maxCellDefaults as $breakpoint => $maxCellDefault) {
                                if ($maxCellDefault->getNumerator() < $maxCells) {
                                    $maxCellDefaultsFiltered[$breakpoint] = $maxCellDefault;
                                }
                            }
                        } else {
                            $maxCellDefaultsFiltered = $maxCellDefaults;
                        }
                        $maxCellsArray = array_merge($maxCellDefaultsFiltered, $maxCellsArray);
                        foreach ($maxCellsArray as $maxCell) {
                            /**
                             * @var ConditionalLength $maxCell
                             */
                            try {
                                $openingCall->addClassName($maxCell->toRowColsClass());
                            } catch (ExceptionBadArgument $e) {
                                LogUtility::error("Error while adding the row-col class. Error: {$e->getMessage()}");
                            }
                        }
                        break;
                    case self::TYPE_WIDTH_SPECIFIED:

                        foreach ($childrenOpeningTags as $cellOpeningTag) {
                            $widthAttributeValue = $cellOpeningTag->getAttribute(Dimension::WIDTH_KEY);
                            $cellOpeningTag->removeAttribute(Dimension::WIDTH_KEY);
                            if ($widthAttributeValue === null) {
                                continue;
                            }
                            $widthValues = explode(" ", $widthAttributeValue);
                            $widthClasses["xs"] = "col-12";
                            foreach ($widthValues as $width) {
                                try {
                                    $conditionalLengthObject = ConditionalLength::createFromString($width);
                                } catch (ExceptionBadArgument $e) {
                                    LogUtility::error("The width value ($width) is not valid length. Error: {$e->getMessage()}");
                                    continue;
                                }
                                $breakpoint = $conditionalLengthObject->getBreakpointOrDefault();
                                try {
                                    $widthClasses[$breakpoint] = $conditionalLengthObject->toColClass();
                                } catch (ExceptionBadArgument $e) {
                                    LogUtility::error("The conditional length $conditionalLengthObject could not be transformed as col class. Error: {$e->getMessage()}");
                                }
                            }
                            foreach ($widthClasses as $widthClass) {
                                $cellOpeningTag->addClassName($widthClass);
                            }
                        }
                        break;
                    case self::TYPE_FIT_VALUE:
                        break;
                    default:
                        LogUtility::error("The grid type ($type) is unknown.", self::CANONICAL);
                }

                /**
                 * Template child callstack ?
                 */
                if ($templateEndTag !== null && $callStackTemplate !== null) {
                    $templateEndTag->setPluginData(syntax_plugin_combo_template::CALLSTACK, $callStackTemplate->getStack());
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingCall->getAttributes()
                );


        }

        return array();

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     *
     *
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {

        if ($format == 'xhtml') {

            /** @var Doku_Renderer_xhtml $renderer */
            $state = $data[PluginUtility::STATE];
            switch ($state) {

                case DOKU_LEXER_ENTER :
                    $attributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES], self::TAG)
                        ->setKnownTypes(self::KNOWN_TYPES);


                    /**
                     * Type
                     */
                    $type = $attributes->getType();
                    if ($type === self::TYPE_FIT_VALUE) {
                        $attributes->addClassName("d-flex");
                    } else {
                        $attributes->addClassName("row");
                    }

                    /**
                     * Gutter
                     */
                    $gutterAttributeValue = $attributes->getValueAndRemoveIfPresent(self::GUTTER);
                    $gutters = explode(" ", $gutterAttributeValue);
                    foreach ($gutters as $gutter) {
                        $attributes->addClassName("g$gutter");
                    }


                    /**
                     * Render
                     */
                    $htmlElement = $attributes->getValueAndRemove(self::HTML_TAG_ATT,"div");
                    $renderer->doc .= $attributes->toHtmlEnterTag($htmlElement);
                    break;

                case DOKU_LEXER_UNMATCHED :

                    $renderer->doc .= PluginUtility::renderUnmatched($data);
                    break;

                case DOKU_LEXER_EXIT :

                    $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                    $htmlElement = $tagAttributes->getValue(self::HTML_TAG_ATT);
                    $renderer->doc .= "</$htmlElement>" . DOKU_LF;
                    break;
            }
            return true;
        }
        return false;
    }


}
