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
use ComboStrap\Spacing;
use ComboStrap\TagAttributes;

if (!defined('DOKU_INC')) {
    die();
}

require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * The implementation of row/col system of Boostrap is called a grid because:
 *   * the children may create may be layout on more than one line
 *   * you can define gutter between the children
 *   * even if this is a layout component that works only on one axis and not two. There is little chance that a user will use the css grid layout system
 * to layout content
 *
 */
class syntax_plugin_combo_grid extends DokuWiki_Syntax_Plugin
{

    const TAG = self::GRID_TAG;
    const GRID_TAG = "grid";
    const TAGS = [self::TAG, self::ROW_TAG];
    const ROW_TAG = "row";

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
     * The type row is a hack to be able
     * to support a row tag (ie flex div)
     *
     * Ie we migrate row to grid smoothly without loosing
     * the possibility to use row as component
     */
    const TYPE_ROW_TAG = "row";


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
        /**
         * Not stack, otherwise you get extra p's
         * and it will fucked up the flex layout
         */
        return 'block';
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


                $callStack = CallStack::createFromHandler($handler);
                $parent = $callStack->moveToParent();

                /**
                 * We have split row in two:
                 *   * grid for a bootstrap grid
                 *   * row for a flex item (contained for now)
                 *
                 * We check
                 */
                $rowMatchPrefix = "<row";
                $isRowTag = substr($match, 0, strlen($rowMatchPrefix)) == $rowMatchPrefix;
                if ($parent != false
                    && !in_array($parent->getTagName(), [
                        syntax_plugin_combo_bar::TAG,
                        syntax_plugin_combo_container::TAG,
                        syntax_plugin_combo_cell::TAG,
                        syntax_plugin_combo_iterator::TAG,
                    ])
                    && $isRowTag
                ) {
                    // contained not in one
                    $scannedType = self::ROW_TAG;
                } else {
                    $scannedType = self::GRID_TAG;
                    if ($isRowTag) {
                        LogUtility::warning("A non-contained row has been deprecated for grid. You should rename the <row> tag to <grid>");
                    }
                }

                $knownTypes = self::KNOWN_TYPES;

                $defaultAttributes = [];
                if ($scannedType === self::GRID_TAG) {

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
                        self::GUTTER => $defaultGutter,
                        Spacing::SPACING_ATTRIBUTE => "mb-3"
                    ];
                    /**
                     * All element are centered
                     * If their is 5 cells and the last one
                     * is going at the line, it will be centered
                     * Y = top (the default of css)
                     */
                    $defaultAlign[Align::X_AXIS] = Align::X_CENTER_CHILDREN;
                } else {
                    /**
                     * Row is for now mainly use in a content-list and the content
                     * should be centered on y
                     * Why ? Because by default, a flex place text at the top and if a badge is added
                     * for instance, it will shift the text towards the top
                     */
                    $defaultAlign[Align::Y_AXIS] = "y-center-children";
                }
                $attributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes);


                if ($scannedType === self::ROW_TAG) {
                    $attributes->setType(self::TYPE_ROW_TAG);
                }

                /**
                 * Align
                 */
                try {
                    $aligns = $attributes->getValues(Align::ALIGN_ATTRIBUTE, []);
                    $alignsByAxis = [];
                    foreach ($aligns as $align) {
                        $alignObject = ConditionalLength::createFromString($align);
                        $alignsByAxis[$alignObject->getAxisOrDefault()] = $align;
                    }
                    foreach ($defaultAlign as $axis => $value) {
                        if (!isset($alignsByAxis[$axis])) {
                            $attributes->addComponentAttributeValue(Align::ALIGN_ATTRIBUTE, $value);
                        }
                    }
                } catch (ExceptionBadArgument $e) {
                    LogUtility::error("The align attribute default values could not be processed. Error: {$e->getMessage()}");
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
                    $attributes->setType(self::TYPE_ROW_TAG);
                    LogUtility::warning("Deprecation: The type value (" . self::TYPE_FIT_VALUE . " and " . self::TYPE_FIT_OLD_VALUE . ") for a contained row tag.", self::CANONICAL);
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
                $maxLineAttributeValue = null; // variable declaration to not have a linter warning
                /**
                 * @var ConditionalLength[] $maxLineArray
                 */
                $maxLineArray = []; // variable declaration to not have a linter warning
                if ($type == null) {

                    $maxLineAttributeValue = $openingCall->getAttribute(self::MAX_CHILDREN_ATTRIBUTE);
                    if ($maxLineAttributeValue !== null) {

                        $maxCellsValues = explode(" ", $maxLineAttributeValue);
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
                            $maxLineArray[$maxCellLength->getBreakpointOrDefault()] = $maxCellLength;
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


                if ($type !== self::TYPE_ROW_TAG) {

                    /**
                     * Scan and process the children for a grid tag
                     * - Add the col class
                     * - Do the cells have a width set ...
                     */
                    foreach ($childrenOpeningTags as $actualCall) {

                        $actualCall->addClassName("col");

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
                                    if ($conditionalLengthObject->isRatio()) {
                                        $ratio = $conditionalLengthObject->getRatio();
                                        if ($ratio > 1) {
                                            LogUtility::warning("The ratio ($ratio) of the width ($conditionalLengthObject) should not be greater than 1 on the children of the row", self::CANONICAL);
                                            break;
                                        }
                                    }
                                } catch (ExceptionBadArgument $e) {
                                    $type = null;
                                    LogUtility::error("The ratio of the width ($conditionalLengthObject) is not a valid. Error: {$e->getMessage()}");
                                    break;
                                }
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
                        $maxLineDefaults = [];
                        try {
                            $maxLineDefaults["xs"] = ConditionalLength::createFromString("1-xs");
                            $maxLineDefaults["sm"] = ConditionalLength::createFromString("2-sm");
                            $maxLineDefaults["md"] = ConditionalLength::createFromString("3-md");
                            $maxLineDefaults["lg"] = ConditionalLength::createFromString("4-lg");
                        } catch (ExceptionBadArgument $e) {
                            LogUtility::error("Bad default value initialization. Error:{$e->getMessage()}", self::CANONICAL);
                        }
                        /**
                         * Delete the default that are bigger than:
                         *   * the asked max-line number
                         *   * or the number of children (ie if there is two children, they split the space in two)
                         */
                        $maxLineDefaultsFiltered = [];
                        $maxLineUsedToFilter = sizeof($childrenOpeningTags);
                        if ($maxLineAttributeValue !== null && $maxLineUsedToFilter > $maxLineAttributeValue) {
                            $maxLineUsedToFilter = $maxLineAttributeValue;
                        }
                        foreach ($maxLineDefaults as $breakpoint => $maxLineDefault) {
                            if ($maxLineDefault->getNumerator() <= $maxLineUsedToFilter) {
                                $maxLineDefaultsFiltered[$breakpoint] = $maxLineDefault;
                            }
                        }
                        $maxLineArray = array_merge($maxLineDefaultsFiltered, $maxLineArray);
                        foreach ($maxLineArray as $maxCell) {
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

                        foreach ($childrenOpeningTags as $childOpeningTag) {
                            $widthAttributeValue = $childOpeningTag->getAttribute(Dimension::WIDTH_KEY);
                            if ($widthAttributeValue === null) {
                                continue;
                            }
                            $widthValues = explode(" ", $widthAttributeValue);
                            $widthColClasses = null;
                            foreach ($widthValues as $width) {
                                try {
                                    $conditionalLengthObject = ConditionalLength::createFromString($width);
                                } catch (ExceptionBadArgument $e) {
                                    LogUtility::error("The width value ($width) is not valid length. Error: {$e->getMessage()}");
                                    continue;
                                }
                                if (!$conditionalLengthObject->isRatio()) {
                                    continue;
                                }
                                $breakpoint = $conditionalLengthObject->getBreakpointOrDefault();
                                try {
                                    $widthColClasses[$breakpoint] = $conditionalLengthObject->toColClass();
                                    $childOpeningTag->removeAttribute(Dimension::WIDTH_KEY);
                                } catch (ExceptionBadArgument $e) {
                                    LogUtility::error("The conditional length $conditionalLengthObject could not be transformed as col class. Error: {$e->getMessage()}");
                                }
                            }
                            if ($widthColClasses !== null) {
                                if (!isset($widthColClasses["xs"])) {
                                    $widthColClasses["xs"] = "col-12";
                                }
                                foreach ($widthColClasses as $widthClass) {
                                    $childOpeningTag->addClassName($widthClass);
                                }
                            }
                        }
                        break;
                    case self::TYPE_ROW_TAG:
                        /**
                         * For all box children that is not the last
                         * one, add a padding right
                         */
                        $length = sizeof($childrenOpeningTags) - 1;
                        for ($i = 0; $i < $length; $i++) {
                            $childOpeningTag = $childrenOpeningTags[$i];
                            if ($childOpeningTag->getDisplay() === Call::BlOCK_DISPLAY) {
                                $spacing = $childOpeningTag->getAttribute(Spacing::SPACING_ATTRIBUTE);
                                if ($spacing === null) {
                                    $childOpeningTag->setAttribute(Spacing::SPACING_ATTRIBUTE, "me-3");
                                }
                            }
                        }
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
                    if ($type === self::TYPE_ROW_TAG) {

                        $attributes->addClassName("d-flex");

                    } else {

                        $attributes->addClassName("row");

                        /**
                         * Gutter
                         */
                        $gutterAttributeValue = $attributes->getValueAndRemoveIfPresent(self::GUTTER);
                        $gutters = explode(" ", $gutterAttributeValue);
                        foreach ($gutters as $gutter) {
                            $attributes->addClassName("g$gutter");
                        }

                    }

                    /**
                     * Render
                     */
                    $htmlElement = $attributes->getValueAndRemove(self::HTML_TAG_ATT, "div");
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

