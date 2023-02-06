<?php


require_once(__DIR__ . "/../ComboStrap/PluginUtility.php");

// must be run within Dokuwiki
use ComboStrap\Breadcrumb;
use ComboStrap\HrTag;
use ComboStrap\IconTag;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\SearchTag;
use ComboStrap\TagAttributes;


/**
 * The empty pattern / void element
 */
class syntax_plugin_combo_tagempty extends DokuWiki_Syntax_Plugin
{


    function getType(): string
    {
        return 'substition';
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - Inline
     *  * 'block' - Block (p are not created inside)
     *  * 'stack' - Block (p can be created inside)
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

    function getAllowedTypes()
    {
        return array();
    }

    function getSort()
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

        $logicalTag = PluginUtility::getTag($match);
        $defaultAttributes = [];
        $knownTypes = [];
        /**
         * Common
         */
        $commonReturnedAttributes[PluginUtility::STATE] = $state;
        $commonReturnedAttributes[PluginUtility::TAG] = $logicalTag;
        switch ($logicalTag) {
            case SearchTag::TAG:
                $defaultAttributes = array(
                    'ajax' => true,
                    'autocomplete' => false
                );
                break;
            case IconTag::TAG:
                $attributes = IconTag::handleSpecial($match, $handler);
                return array_merge($commonReturnedAttributes, $attributes);
            case Breadcrumb::TAG:
                $knownTypes = Breadcrumb::TYPES;
                $defaultAttributes = [TagAttributes::TYPE_KEY => Breadcrumb::NAVIGATION_TYPE];
                break;
        }
        $tag = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes);
        $defaultArray = array(PluginUtility::ATTRIBUTES => $tag->toCallStackArray());
        return array_merge($commonReturnedAttributes, $defaultArray);

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

        $tag = $data[PluginUtility::TAG];
        $attributes = $data[PluginUtility::ATTRIBUTES];
        $tagAttributes = TagAttributes::createFromCallStackArray($attributes)
            ->setLogicalTag($tag);
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
                    default:
                        LogUtility::errorIfDevOrTest("The empty tag (" . $tag . ") was not processed.");
                }
                break;
            case 'metadata':
                /** @var Doku_Renderer_metadata $renderer */
                if ($tag == IconTag::TAG) {
                    IconTag::metadata($renderer, $tagAttributes);
                }
                break;
        }
        // unsupported $mode
        return false;
    }


}

