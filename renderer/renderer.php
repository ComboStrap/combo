<?php

use ComboStrap\Tag\AdTag;
use ComboStrap\FsWikiUtility;
use ComboStrap\HeadingTag;
use ComboStrap\Xml\XhtmlUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Tag\TableTag;
use ComboStrap\Toc;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');


/**
 * Class renderer_plugin_combo_renderer
 * The last two parts ie `combo_renderer` is the id for dokuwiki
 * The last part should also be equal to the name
 */
class  renderer_plugin_combo_renderer extends Doku_Renderer_xhtml
{
    const COMBO_RENDERER_NAME = 'combo_renderer';

    /**
     * @var array that hold the position of the parent
     */
    protected $nodeParentPosition = [];

    /**
     * @var array that hold the current position of an header for a level
     * $headerNum[level]=position
     */
    protected $header = [];

    /**
     * @var array that will contains the whole doc but by section
     */
    protected $sections = [];

    /**
     * @var int the section number
     */
    protected $sectionNumber = 0;

    /**
     * @var string variable that permits to carry the header text of a previous section
     */
    protected $previousSectionTextHeader = '';


    /**
     * @var int variable that permits to carry the position of a previous section
     */
    protected $previousNodePosition = 0;

    /**
     * @var int variable that permits to carry the position of a previous section
     */
    protected $previousNodeLevel = 0;

    /**
     * @var int variable that permits to carry the number of words
     */
    protected $lineCounter = 0;


    function getFormat()
    {
        return 'xhtml';
    }

    /*
     * Function that enable to list the plugin in the options for config:renderer_xhtml
     * http://www.dokuwiki.org/config:renderer_xhtml
     * setting in its Configuration Manager.
     */
    public function canRender($format)
    {
        return ($format == 'xhtml');
    }


    /**
     * Render a heading
     *
     * The rendering of the heading is done through the parent
     * The function just:
     *   - save the rendering between each header in the class variable $this->sections
     * This variblae is used in the function document_end to recreate the whole doc.
     *   - add the numbering to the header text
     *
     * @param string $text the text to display
     * @param int $level header level
     * @param int $pos byte position in the original source
     */
    function header($text, $level, $pos, $returnonly = false)
    {


        // We are going from 2 to 3
        // The parent is 2
        if ($level > $this->previousNodeLevel) {
            $nodePosition = 1;
            // Keep the position of the parent
            $this->nodeParentPosition[$this->previousNodeLevel] = $this->previousNodePosition;
        } elseif
            // We are going from 3 to 2
            // The parent is 1
        ($level < $this->previousNodeLevel
        ) {
            $nodePosition = $this->nodeParentPosition[$level] + 1;
        } else {
            $nodePosition = $this->previousNodePosition + 1;
        }

        // Grab the doc from the previous section
        $this->sections[$this->sectionNumber] = array(
            'level' => $this->previousNodeLevel,
            'position' => $this->previousNodePosition,
            'content' => $this->doc,
            'text' => $this->previousSectionTextHeader);

        // And reset it
        $this->doc = '';
        // Set the looping variable
        $this->sectionNumber = $this->sectionNumber + 1;
        $this->previousNodeLevel = $level;
        $this->previousNodePosition = $nodePosition;
        $this->previousSectionTextHeader = $text;


        /**
         * Rendering is done by the parent
         * And should be the last one
         * Because we delete the heading
         * with {@link HeadingTag::reduceToFirstOpeningTagAndReturnAttributes()}
         * in order to be able to add the toc and section
         *
         */
        parent::header($text, $level, $pos);


    }


    function document_end()
    {

        global $ID;
        // The id of the page (not of the sidebar)
        $id = $ID;
        $isSidebar = FsWikiUtility::isSideBar();


        // Pump the last doc
        $this->sections[$this->sectionNumber] = array('level' => $this->previousNodeLevel, 'position' => $this->previousNodePosition, 'content' => $this->doc, 'text' => $this->previousSectionTextHeader);

        // Recreate the doc
        $this->doc = '';
        $rollingLineCount = 0;
        $currentLineCountSinceLastAd = 0;
        foreach ($this->sections as $sectionNumber => $section) {

            $sectionContent = $section['content'];

            # Split by element line
            # element p, h, br, tr, li, pre (one line for pre)
            $sectionLineCount = XhtmlUtility::countLines($sectionContent);
            $currentLineCountSinceLastAd += $sectionLineCount;
            $rollingLineCount += $sectionLineCount;

            // The content
            if ($this->getConf('ShowCount') == 1) {
                $this->doc .= "<p>Section " . $sectionNumber . ": (" . $sectionLineCount . "|" . $currentLineCountSinceLastAd . "|" . $rollingLineCount . ")</p>";
            }
            $this->doc .= $sectionContent;


        }

        parent::document_end();

    }


}
