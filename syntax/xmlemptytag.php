<?php


require_once(__DIR__ . '/../vendor/autoload.php');

use ComboStrap\BackgroundTag;
use ComboStrap\Brand;
use ComboStrap\BrandButton;
use ComboStrap\BrandListTag;
use ComboStrap\BrandTag;
use ComboStrap\Breadcrumb;
use ComboStrap\CacheTag;
use ComboStrap\CallStack;
use ComboStrap\HrTag;
use ComboStrap\IconTag;
use ComboStrap\LogUtility;
use ComboStrap\PageImageTag;
use ComboStrap\PluginUtility;
use ComboStrap\SearchTag;
use ComboStrap\ShareTag;
use ComboStrap\TagAttributes;


/**
 * The empty pattern / void element
 */
class syntax_plugin_combo_xmlemptytag extends DokuWiki_Syntax_Plugin
{

    // should be the same than the last name of the class name
    const TAG = "xmlemptytag";

    function getType(): string
    {
        return 'substition';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - Inline
     *  * 'block' - Block (dokuwiki does not create p inside)
     *  * 'stack' - Block (dokuwiki creates p inside)
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType(): string
    {
        /**
         * Empty tag may be also block (ie Navigational {@link Breadcrumb} for instance
         */
        return 'normal';
    }

    function getAllowedTypes(): array
    {
        return array();
    }

    function getSort(): int
    {
        return 201;
    }


    function connectTo($mode)
    {

        $pattern = PluginUtility::getEmptyTagPatternGeneral();
        $this->Lexer->addSpecialPattern($pattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

    }


    function handle($match, $state, $pos, Doku_Handler $handler): array
    {

        /**
         * Logical Tag Building
         */
        $logicalTag = PluginUtility::getMarkupTag($match);
        $defaultAttributes = [];
        $knownTypes = [];
        $allowAnyFirstBooleanAttributesAsType = false;
        switch ($logicalTag) {
            case SearchTag::TAG:
                $defaultAttributes = array(
                    'ajax' => true,
                    'autocomplete' => false
                );
                break;
            case Breadcrumb::TAG:
                $knownTypes = Breadcrumb::TYPES;
                $defaultAttributes = [TagAttributes::TYPE_KEY => Breadcrumb::NAVIGATION_TYPE];
                break;
            case PageImageTag::MARKUP:
                $knownTypes = PageImageTag::TYPES;
                break;
            case ShareTag::MARKUP:
                $knownTypes = Brand::getBrandNamesForButtonType(BrandButton::TYPE_BUTTON_SHARE);
                break;
            case BrandListTag::MARKUP:
                $knownTypes = BrandButton::TYPE_BUTTONS;
                $defaultAttributes = [TagAttributes::TYPE_KEY => BrandButton::TYPE_BUTTON_BRAND];
                break;
            case BrandTag::MARKUP:
                $defaultAttributes = [TagAttributes::TYPE_KEY => Brand::CURRENT_BRAND];
                $allowAnyFirstBooleanAttributesAsType = true;
                break;
        }
        $tagAttributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes, $allowAnyFirstBooleanAttributesAsType)
            ->setLogicalTag($logicalTag);

        /**
         * Calculate extra returned key in the table
         */
        $returnedArray = [];
        switch ($logicalTag) {
            case IconTag::TAG:
                $returnedArray = IconTag::handleSpecial($tagAttributes, $handler);
                break;
            case PageImageTag::MARKUP:
                $returnedArray = PageImageTag::handle($tagAttributes, $handler);
                break;
            case BrandTag::MARKUP:
                $returnedArray = BrandTag::handle($tagAttributes, $handler);
                break;
            case CacheTag::MARKUP:
                $returnedArray = CacheTag::handle($tagAttributes);
                break;
            case BackgroundTag::MARKUP_SHORT:
            case BackgroundTag::MARKUP_LONG:
                BackgroundTag::modifyColorAttributes($tagAttributes);
                $callStack = CallStack::createFromHandler($handler);
                $returnedArray = BackgroundTag::setAttributesToParentAndReturnData($callStack, $tagAttributes, $state);
                break;
        }

        /**
         * Common default
         */
        $defaultReturnedArray[PluginUtility::STATE] = $state;
        $defaultReturnedArray[PluginUtility::TAG] = $logicalTag;
        $defaultReturnedArray[PluginUtility::ATTRIBUTES] = $tagAttributes->toCallStackArray();

        return array_merge($defaultReturnedArray, $returnedArray);

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {

        $tag = $data[PluginUtility::TAG];
        $attributes = $data[PluginUtility::ATTRIBUTES];
        $state = DOKU_LEXER_SPECIAL;
        $tagAttributes = TagAttributes::createFromCallStackArray($attributes)->setLogicalTag($tag);
        switch ($format) {
            case "xhtml":
                /** @var Doku_Renderer_xhtml $renderer */
                switch ($tag) {
                    case HrTag::TAG:
                        $renderer->doc .= HrTag::render($tagAttributes);
                        break;
                    case SearchTag::TAG:
                        $renderer->doc .= SearchTag::render($tagAttributes);
                        break;
                    case IconTag::TAG:
                        $renderer->doc .= IconTag::render($tagAttributes);
                        break;
                    case Breadcrumb::TAG:
                        $renderer->doc .= Breadcrumb::render($tagAttributes);
                        break;
                    case PageImageTag::MARKUP:
                        $renderer->doc .= PageImageTag::render($tagAttributes, $data);
                        break;
                    case ShareTag::MARKUP:
                        $renderer->doc .= ShareTag::render($tagAttributes, $state);
                        break;
                    case BrandListTag::MARKUP:
                        $renderer->doc .= BrandListTag::render($tagAttributes);
                        break;
                    case BrandTag::MARKUP:
                        $renderer->doc .= BrandTag::render($tagAttributes, $state, $data);
                        break;
                    case CacheTag::MARKUP:
                        $renderer->doc .= CacheTag::renderXhtml($data);
                        break;
                    case BackgroundTag::MARKUP_LONG:
                    case BackgroundTag::MARKUP_SHORT:
                        $renderer->doc .= BackgroundTag::renderHtml($data);
                        break;
                    default:
                        LogUtility::errorIfDevOrTest("The empty tag (" . $tag . ") was not processed.");
                }
                break;
            case 'metadata':
                /** @var Doku_Renderer_metadata $renderer */
                switch ($tag) {
                    case IconTag::TAG:
                        IconTag::metadata($renderer, $tagAttributes);
                        break;
                    case CacheTag::MARKUP:
                        CacheTag::metadata($data);
                }
                break;
        }
        // unsupported $mode
        return false;
    }


}

