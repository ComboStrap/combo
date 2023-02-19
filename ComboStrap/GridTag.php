<?php

namespace ComboStrap;


use syntax_plugin_combo_cell;
use syntax_plugin_combo_fragment;
use syntax_plugin_combo_iterator;

/**
 * The implementation of row/col system of Boostrap is called a grid because:
 *   * the children may create may be layout on more than one line
 *   * you can define gutter between the children
 *   * even if this is a layout component that works only on one axis and not two. There is little chance that a user will use the css grid layout system
 * to layout content
 *
 */
class GridTag
{


    public const GUTTER = "gutter";
    /**
     * Used when the grid is not contained
     * and is just below the root
     * We set a value
     * @deprecated (30/04/2022)
     */
    public const TYPE_AUTO_VALUE_DEPRECATED = "auto";
    /**
     * By default, div but used in a ul, it could be a li
     * This is modified in the callstack by the other component
     * @deprecated with the new {@link Align} (30/04/2022)
     */
    public const HTML_TAG_ATT = "html-tag";
    public const KNOWN_TYPES = [self::TYPE_MAX_CHILDREN, GridTag::TYPE_WIDTH_SPECIFIED, GridTag::TYPE_AUTO_VALUE_DEPRECATED, GridTag::TYPE_FIT_VALUE, GridTag::TYPE_FIT_OLD_VALUE];
    public const GRID_TAG = "grid";
    public const ROW_TAG = "row";
    /**
     *
     * @deprecated - contained/fit type was the same and has been deprecated for the {@link Align} attribute and the width value (grow/shrink)
     * (30/04/2022)
     */
    public const TYPE_FIT_VALUE = "fit";
    /**
     * The type row is a hack to be able
     * to support a row tag (ie flex div)
     *
     * Ie we migrate row to grid smoothly without loosing
     * the possibility to use row as component
     */
    public const TYPE_ROW_TAG = "row";
    /**
     * @deprecated (30/04/2022)
     */
    public const TYPE_FIT_OLD_VALUE = "natural";
    public const MAX_CHILDREN_ATTRIBUTE = "max-line";
    /**
     * The value is not `width` as this is also an
     * attribute {@link Dimension::WIDTH_KEY}
     * and it will fail the type check at {@link TagAttributes::hasComponentAttribute()}
     */
    public const TYPE_WIDTH_SPECIFIED = "width-specified";
    public const TAG = GridTag::GRID_TAG;
    /**
     * The strap template permits to
     * change this value
     * but because of the new grid system, it has been deprecated
     * We therefore don't get the grid total columns value from strap
     * @see {@link https://combostrap.com/dynamic_grid Dynamic Grid }
     */
    public const GRID_TOTAL_COLUMNS = 12;
    public const TAGS = [GridTag::TAG, GridTag::ROW_TAG];
    public const TYPE_MAX_CHILDREN = "max";
    public const CANONICAL = GridTag::TAG;
    const LOGICAL_TAG = self::GRID_TAG;


    public static function processEnter(TagAttributes $attributes, $handler, $match)
    {

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
                BarTag::BAR_TAG,
                ContainerTag::TAG,
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
                LogUtility::warning("A non-contained row has been deprecated for grid. You should rename the row tag to grid");
            }
        }

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
            /**
             * This is a block
             * as we give it the same spacing than
             * a paragraph
             */
            $spacing = "mb-3";
            $defaultAttributes = [
                self::GUTTER => $defaultGutter,
                Spacing::SPACING_ATTRIBUTE => $spacing
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


        if ($scannedType === self::ROW_TAG) {
            $attributes->setType(self::TYPE_ROW_TAG);
        }

        /**
         * Default
         */
        foreach ($defaultAttributes as $key => $value) {
            if (!$attributes->hasComponentAttribute($key)) {
                $attributes->addComponentAttributeValue($key, $value);
            }
        }

        /**
         * Align default
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

    }

    public static function handleExit(\Doku_Handler $handler): array
    {

        $callStack = CallStack::createFromHandler($handler);

        /**
         * The returned array
         * (filed while processing)
         */
        $returnArray = array();

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

            $maxLineAttributeValue = $openingCall->getAttribute(GridTag::MAX_CHILDREN_ATTRIBUTE);
            if ($maxLineAttributeValue !== null) {

                $maxCellsValues = explode(" ", $maxLineAttributeValue);
                foreach ($maxCellsValues as $maxCellsValue) {
                    try {
                        $maxCellLength = ConditionalLength::createFromString($maxCellsValue);
                    } catch (ExceptionBadArgument $e) {
                        LogUtility::error("The max-cells attribute value ($maxCellsValue) is not a valid length value. Error: {$e->getMessage()}", GridTag::CANONICAL);
                        continue;
                    }
                    $number = $maxCellLength->getNumerator();
                    if ($number > 12) {
                        LogUtility::error("The max-cells attribute value ($maxCellsValue) should be less than 12.", GridTag::CANONICAL);
                    }
                    $maxLineArray[$maxCellLength->getBreakpointOrDefault()] = $maxCellLength;
                }
                $openingCall->removeAttribute(GridTag::MAX_CHILDREN_ATTRIBUTE);
                $type = GridTag::TYPE_MAX_CHILDREN;
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
        if ($firstChildTag->getTagName() === syntax_plugin_combo_fragment::TAG && $firstChildTag->getState() === DOKU_LEXER_ENTER) {
            $templateEndTag = $callStack->next();
            if ($templateEndTag->getTagName() !== syntax_plugin_combo_fragment::TAG || $templateEndTag->getState() !== DOKU_LEXER_EXIT) {
                LogUtility::error("Error internal: We were unable to find the closing template tag.", GridTag::CANONICAL);
                return $returnArray;
            }
            $templateInstructions = $templateEndTag->getPluginData(syntax_plugin_combo_fragment::CALLSTACK);
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


        if ($type !== GridTag::TYPE_ROW_TAG) {

            /**
             * Scan and process the children for a grid tag
             * - Add the col class
             * - Do the cells have a width set ...
             */
            foreach ($childrenOpeningTags as $actualCall) {

                $actualCall->addClassName("col");

                $widthAttributeValue = $actualCall->getAttribute(Dimension::WIDTH_KEY);
                if ($widthAttributeValue !== null) {
                    $type = GridTag::TYPE_WIDTH_SPECIFIED;
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
                                    LogUtility::warning("The ratio ($ratio) of the width ($conditionalLengthObject) should not be greater than 1 on the children of the row", GridTag::CANONICAL);
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
            $type = GridTag::TYPE_MAX_CHILDREN;
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
            case GridTag::TYPE_MAX_CHILDREN:
                $maxLineDefaults = [];
                try {
                    $maxLineDefaults["xs"] = ConditionalLength::createFromString("1-xs");
                    $maxLineDefaults["sm"] = ConditionalLength::createFromString("2-sm");
                    $maxLineDefaults["md"] = ConditionalLength::createFromString("3-md");
                    $maxLineDefaults["lg"] = ConditionalLength::createFromString("4-lg");
                } catch (ExceptionBadArgument $e) {
                    LogUtility::error("Bad default value initialization. Error:{$e->getMessage()}", GridTag::CANONICAL);
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
            case GridTag::TYPE_WIDTH_SPECIFIED:

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
            case GridTag::TYPE_ROW_TAG:
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
                LogUtility::error("The grid type ($type) is unknown.", GridTag::CANONICAL);
        }

        /**
         * Template child callstack ?
         */
        if ($templateEndTag !== null && $callStackTemplate !== null) {
            $templateEndTag->setPluginData(syntax_plugin_combo_fragment::CALLSTACK, $callStackTemplate->getStack());
        }

        return array(
            PluginUtility::ATTRIBUTES => $openingCall->getAttributes()
        );
    }

    public static function renderEnterXhtml(TagAttributes $attributes): string
    {

        /**
         * Type
         */
        $type = $attributes->getType();
        if ($type === GridTag::TYPE_ROW_TAG) {

            $attributes->addClassName("d-flex");

        } else {

            $attributes->addClassName("row");

            /**
             * Gutter
             */
            $gutterAttributeValue = $attributes->getValueAndRemoveIfPresent(GridTag::GUTTER);
            $gutters = explode(" ", $gutterAttributeValue);
            foreach ($gutters as $gutter) {
                $attributes->addClassName("g$gutter");
            }

        }

        /**
         * Render
         */
        $htmlElement = $attributes->getValueAndRemove(GridTag::HTML_TAG_ATT, "div");
        return $attributes->toHtmlEnterTag($htmlElement);

    }


    public static function renderExitXhtml(TagAttributes $tagAttributes): string
    {
        $htmlElement = $tagAttributes->getValue(GridTag::HTML_TAG_ATT);
        return "</$htmlElement>";
    }


}
