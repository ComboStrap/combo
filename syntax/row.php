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
 * The {@link https://combostrap.com/row row} of a {@link https://combostrap.com/grid grid}
 *
 *
 * Note: The name of the class must follow this pattern ie syntax_plugin_PluginName_ComponentName
 *
 *
 * See also: https://getbootstrap.com/docs/5.0/utilities/flex/
 */
class syntax_plugin_combo_row extends DokuWiki_Syntax_Plugin
{

    const TAG = "row";
    const SNIPPET_ID = "row";

    /**
     * The strap template permits to
     * change this value
     * but because of the new grid system, it has been deprecated
     * We therefore don't get the grid total columns value from strap
     * @see {@link https://combostrap.com/dynamic_grid Dynamic Grid }
     */
    const GRID_TOTAL_COLUMNS = 12;


    const CANONICAL = self::ROW;
    const ROW = "row";

    /**
     * A row can be used as a grid
     * with the div element
     * or as a list item
     *
     * By default, this is a div but a list
     * or any other component can change that
     */
    const HTML_TAG_ATT = "html-tag";

    /**
     * Meant to be a children of a component
     * Vertically centered and no padding on the first cell and last cell
     *
     * Used in @link syntax_plugin_combo_contentlist} or
     * within a card for instance
     *
     * This value is not yet public or in the documentation
     */
    const CONTAINED_CONTEXT = "contained";
    const ROOT_CONTEXT = "root";

    /**
     * Used when the grid is not contained
     * and is just below the root
     * We set a value
     */
    const TYPE_AUTO_VALUE_DEPRECATED = "auto";
    const TYPE_FIT_OLD_VALUE = "natural";
    const TYPE_FIT_VALUE = "fit";
    const MINIMAL_WIDTH = 300;

    /**
     * The {@link syntax_plugin_combo_contentlist}
     * component use row under the hood
     * and add its own class, this attribute
     * helps to see if the user has enter any class
     */
    const HAD_USER_CLASS = "hasClass";
    const TYPE_WIDTH_SPECIFIED = "width";
    const KNOWN_TYPES = [self::TYPE_WIDTH_SPECIFIED, self::TYPE_AUTO_VALUE_DEPRECATED, self::TYPE_FIT_VALUE, self::TYPE_FIT_OLD_VALUE];
    const MAX_CHILDREN_ATTRIBUTE = "max-line";
    const TYPE_MAX_CHILDREN = "max";


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


        $pattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));


    }

    public function postConnect()
    {

        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));

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
                $defaultAttributes = [
                    Align::ALIGN_ATTRIBUTE => "x-center-children"
                ];
                $attributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes);

                $type = $attributes->getType();
                if (($type === self::TYPE_AUTO_VALUE_DEPRECATED)) {
                    LogUtility::warning("The auto rows type has been deprecated.", self::CANONICAL);
                    $attributes->removeType();
                }


                $callStack = CallStack::createFromHandler($handler);
                $parent = $callStack->moveToParent();

                /**
                 * The deprecation
                 */
                if ($attributes->hasComponentAttribute(TagAttributes::TYPE_KEY)) {
                    $value = $attributes->getType();
                    if ($value == self::TYPE_FIT_OLD_VALUE) {
                        $attributes->setType(self::TYPE_FIT_VALUE);
                        LogUtility::warning("Deprecation: The type value (" . self::TYPE_FIT_OLD_VALUE . ") for the row component should be been renamed to (" . self::TYPE_FIT_VALUE . ")", self::CANONICAL);
                    }
                }

                /**
                 * Context
                 *   To add or not a margin-bottom,
                 *   To delete the image link or not
                 */
                $context = self::ROOT_CONTEXT;
                if ($parent != false
                    && !in_array($parent->getTagName(), [
                        syntax_plugin_combo_bar::TAG,
                        syntax_plugin_combo_container::TAG,
                        syntax_plugin_combo_cell::TAG,
                        syntax_plugin_combo_iterator::TAG
                    ])) {
                    $context = self::CONTAINED_CONTEXT;
                }


                /**
                 * By default, div but used in a ul, it could be a li
                 * This is modified in the callstack by the other component
                 */
                $attributes->addComponentAttributeValue(self::HTML_TAG_ATT, "div");


                /**
                 * User Class
                 */
                if ($attributes->hasComponentAttribute(TagAttributes::CLASS_KEY)) {
                    $attributes->addComponentAttributeValue(self::HAD_USER_CLASS, true);
                } else {
                    $attributes->addComponentAttributeValue(self::HAD_USER_CLASS, false);
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $attributes->toCallStackArray(),
                    PluginUtility::CONTEXT => $context
                );

            case DOKU_LEXER_UNMATCHED:
                return PluginUtility::handleAndReturnUnmatchedData(self::TAG, $match, $handler);

            case DOKU_LEXER_EXIT :

                $callStack = CallStack::createFromHandler($handler);


                /**
                 * Sizing Type mode determination
                 */
                $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
                $type = $openingCall->getType();

                /**
                 * Max-Cells ?
                 */
                $maxCells = $openingCall->getAttribute(self::MAX_CHILDREN_ATTRIBUTE);
                /**
                 * @var ConditionalLength[] $maxCellsArray
                 */
                $maxCellsArray = [];
                if ($maxCells !== null) {

                    $maxCellsValues = explode(" ", $maxCells);
                    foreach ($maxCellsValues as $maxCellsValue) {
                        try {
                            $maxCellLength = ConditionalLength::createFromString($maxCellsValue, "sm");
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


                /**
                 * Scan the cells children
                 * Add the col class
                 * Do the cells have a width set ...
                 *
                 */
                $childCellOpeningTags = $callStack->getChildren();
                foreach ($childCellOpeningTags as $actualCall) {
                    $actualCall->addClassName("col");
                    $childCellOpeningTags[] = $actualCall;
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

                    if ($openingCall->getContext() === self::CONTAINED_CONTEXT) {
                        $type = self::TYPE_FIT_VALUE;
                    } else {
                        $type = self::TYPE_MAX_CHILDREN;
                    }

                }
                // setting the type on the opening tag to see it in html attribute
                $openingCall->setType($type);


                /**
                 * Distribution calculation
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
                        // Total calculation


                        foreach ($childCellOpeningTags as $cellOpeningTag) {
                            $widthAttributeValue = $cellOpeningTag->getAttribute(Dimension::WIDTH_KEY);
                            $cellOpeningTag->removeAttribute(Dimension::WIDTH_KEY);
                            if ($widthAttributeValue === null) {
                                continue;
                            }
                            $widthValues = explode(" ", $widthAttributeValue);
                            $widthClasses["xs"] = "col-12";
                            foreach ($widthValues as $width) {
                                try {
                                    $conditionalLengthObject = ConditionalLength::createFromString($width, "sm");
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
                    case syntax_plugin_combo_row::TYPE_AUTO_VALUE_DEPRECATED:
                        $numberOfColumns = 0;
                        /**
                         * If the size or the class is set, we don't
                         * apply the automatic sizing
                         */
                        $hasSizeOrClass = false;
                        $callStack->moveToCall($openingCall);
                        while ($actualCall = $callStack->next()) {
                            $tagName = $actualCall->getTagName();
                            if ($tagName == syntax_plugin_combo_cell::TAG
                                &&
                                $actualCall->getState() == DOKU_LEXER_ENTER
                            ) {
                                $numberOfColumns++;
                                if ($actualCall->hasAttribute(syntax_plugin_combo_cell::WIDTH_ATTRIBUTE)) {
                                    $hasSizeOrClass = true;
                                }
                                if ($actualCall->hasAttribute(TagAttributes::CLASS_KEY)) {
                                    $hasSizeOrClass = true;
                                }

                            }
                        }
                        if (!$hasSizeOrClass && $numberOfColumns > 1) {
                            /**
                             * Parameters
                             */
                            $minimalWidth = self::MINIMAL_WIDTH;
                            $breakpoints =
                                [
                                    "xs" => 270,
                                    "sm" => 540,
                                    "md" => 720,
                                    "lg" => 960,
                                    "xl" => 1140,
                                    "xxl" => 1320
                                ];
                            /**
                             * Calculation of the sizes value
                             */
                            $sizes = [];
                            $previousPercentage = null;
                            foreach ($breakpoints as $breakpoint => $viewPortWidth) {
                                $spaceByColumn = $viewPortWidth / $numberOfColumns;
                                if ($spaceByColumn < $minimalWidth) {
                                    $spaceByColumn = $minimalWidth;
                                }
                                try {
                                    $percentage = DataType::toInteger(floor($spaceByColumn / $viewPortWidth * 100));
                                } catch (ExceptionBadArgument $e) {
                                    LogUtility::error("Internal error when calculating the auto percentage. {$e->getMessage()}");
                                    continue;
                                }
                                if ($percentage > 100) {
                                    $percentage = 100;
                                }
                                if ($percentage !== $previousPercentage) {
                                    $sizes[] = "$percentage%-$breakpoint";
                                    $previousPercentage = $percentage;
                                } else {
                                    break;
                                }
                            }
                            $callStack->moveToPreviousCorrespondingOpeningCall();
                            while ($actualCall = $callStack->next()) {
                                if ($actualCall->getTagName() == syntax_plugin_combo_cell::TAG
                                    &&
                                    $actualCall->getState() == DOKU_LEXER_ENTER
                                ) {
                                    foreach ($sizes as $sizeValue) {
                                        try {
                                            $colClass = ConditionalLength::createFromString($sizeValue)->toColClass();
                                        } catch (ExceptionBadArgument $e) {
                                            LogUtility::error("We can't transform the size ($sizeValue) to a col class. Error: {$e->getMessage()}");
                                            continue;
                                        }
                                        $actualCall->addClassName($colClass);
                                    }

                                }
                            }
                        };
                        break;
                    case self::TYPE_FIT_VALUE:
                        /**
                         * No link for the media image by default
                         */
                        $callStack->moveToEnd();
                        $callStack->moveToPreviousCorrespondingOpeningCall();
                        $callStack->processNoLinkOnImageToEndStack();

                        /**
                         * Process the P to make them container friendly
                         * Needed to make the diff between a p added
                         * by the user via the {@link syntax_plugin_combo_para text}
                         * and a p added automatically by Dokuwiki
                         *
                         */
                        $callStack->moveToPreviousCorrespondingOpeningCall();
                        // Follow the bootstrap and combo convention
                        // ie text for bs and combo as suffix
                        $class = "row-contained-text-combo";
                        $callStack->processEolToEndStack(["class" => $class]);

                        /**
                         * If the type is fit value (ie flex auto),
                         * we constraint the cell that have text
                         */
                        $callStack->moveToEnd();
                        $callStack->moveToPreviousCorrespondingOpeningCall();
                        $hasText = false;
                        while ($actualCall = $callStack->next()) {
                            if ($actualCall->getTagName() == syntax_plugin_combo_cell::TAG) {
                                switch ($actualCall->getState()) {
                                    case DOKU_LEXER_ENTER:
                                        $actualCellOpenTag = $actualCall;
                                        $hasText = false;
                                        break;
                                    case DOKU_LEXER_EXIT:
                                        if ($hasText) {
                                            if (isset($actualCellOpenTag) && !$actualCellOpenTag->hasAttribute(Dimension::WIDTH_KEY)) {
                                                $actualCellOpenTag->addAttribute(Dimension::WIDTH_KEY, self::MINIMAL_WIDTH);
                                            }
                                        };
                                        break;
                                }
                            } else if ($actualCall->isTextCall()) {
                                $hasText = true;
                            }

                        };
                        break;
                }

                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::CONTEXT => $openingCall->getContext(),
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
                    $hadClassAttribute = $attributes->getBooleanValueAndRemoveIfPresent(self::HAD_USER_CLASS);
                    $htmlElement = $attributes->getValueAndRemove(self::HTML_TAG_ATT);

                    $attributes->addClassName("row");

                    /**
                     * The type is responsible
                     * for the width and space between the cells
                     */
                    $type = $attributes->getValue(TagAttributes::TYPE_KEY);
                    if (!empty($type)) {
                        switch ($type) {
                            case syntax_plugin_combo_row::TYPE_FIT_VALUE:
                                $attributes->addClassName("row-cols-auto");
                                if (Bootstrap::getBootStrapMajorVersion() != Bootstrap::BootStrapFiveMajorVersion) {
                                    // row-cols-auto is not in 4.0
                                    PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot("row-cols-auto");
                                }
                                break;
                            case syntax_plugin_combo_row::TYPE_AUTO_VALUE_DEPRECATED:
                                /**
                                 * The class are set on the cells, not on the row,
                                 * nothing to do
                                 */
                                break;
                        }
                    }

                    /**
                     * Add the css
                     */
                    $context = $data[PluginUtility::CONTEXT];
                    $tagClass = self::TAG . "-" . $context;

                    switch ($context) {
                        case self::CONTAINED_CONTEXT:
                            /**
                             * All element are centered vertically and horizontally
                             */
                            if (!$hadClassAttribute) {
                                $attributes->addClassName("align-items-center");
                                if (Bootstrap::getBootStrapMajorVersion() === Bootstrap::BootStrapFiveMajorVersion) {
                                    $attributes->addClassName("g-0");
                                } else {
                                    // https://getbootstrap.com/docs/4.3/layout/grid/#no-gutters
                                    $attributes->addClassName("no-gutters");
                                }

                            }
                            /**
                             * p children should be flex
                             * p generated should have no bottom-margin (because contained)
                             */
                            $attributes->addClassName($tagClass);
                            PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot($tagClass);
                            break;
                        case self::ROOT_CONTEXT:

                            if (!$hadClassAttribute) {
                                /**
                                 * Vertical gutter
                                 * On a two cell grid, the content will not
                                 * touch on a mobile
                                 */
                                $attributes->addClassName("gy-5");
                            }
                            $attributes->addClassName($tagClass);
                            PluginUtility::getSnippetManager()->attachCssInternalStyleSheetForSlot($tagClass);
                            break;
                    }


                    /**
                     * Render
                     */
                    $renderer->doc .= $attributes->toHtmlEnterTag($htmlElement) . DOKU_LF;
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

