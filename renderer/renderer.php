<?php

use ComboStrap\AdsUtility;
use ComboStrap\BreadcrumbHierarchical;
use ComboStrap\HtmlUtility;
use ComboStrap\FsWikiUtility;
use ComboStrap\TableUtility;
use ComboStrap\TocUtility;


require_once(__DIR__ . '/../class/FsWikiUtility.php');
require_once(__DIR__ . '/../class/TableUtility.php');
require_once(__DIR__ . '/../class/TocUtility.php');
require_once(__DIR__ . '/../class/AdsUtility.php');
require_once(__DIR__ . '/../class/HtmlUtility.php');
require_once(__DIR__ . '/../class/BreadcrumbHierarchical.php');

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
    function header($text, $level, $pos)
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

        $numbering = "";
        if ($level == 2) {
            $numbering = $nodePosition;
        }
        if ($level == 3) {
            $numbering = $this->nodeParentPosition[$level - 1] . "." . $nodePosition;
        }
        if ($level == 4) {
            $numbering = $this->nodeParentPosition[$level - 2] . "." . $this->nodeParentPosition[$level - 1] . "." . $nodePosition;
        }
        if ($level == 5) {
            $numbering = $this->nodeParentPosition[$level - 3] . "." . $this->nodeParentPosition[$level - 2] . "." . $this->nodeParentPosition[$level - 1] . "." . $nodePosition;
        }
        if ($numbering <> "") {
            $textWithLocalization = $numbering . " - " . $text;
        } else {
            $textWithLocalization = $text;
        }

        // Rendering is done by the parent
        parent::header($textWithLocalization, $level, $pos);


        // Add the page detail after the first header
        if ($level == 1 and $nodePosition == 1) {

            $this->doc .= BreadcrumbHierarchical::render();

        }


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
        $adsCounter = 0;
        foreach ($this->sections as $sectionNumber => $section) {

            $sectionContent = $section['content'];


            if ($section['level'] == 1 and $section['position'] == 1) {

                if (TocUtility::showToc($this)) {
                    $sectionContent .= TocUtility::renderToc($this);
                }

            }

            # Split by element line
            # element p, h, br, tr, li, pre (one line for pre)
            $sectionLineCount = HtmlUtility::countLines($sectionContent);
            $currentLineCountSinceLastAd += $sectionLineCount;
            $rollingLineCount += $sectionLineCount;

            // The content
            if ($this->getConf('ShowCount') == 1 && $isSidebar == FALSE) {
                $this->doc .= "<p>Section " . $sectionNumber . ": (" . $sectionLineCount . "|" . $currentLineCountSinceLastAd . "|" . $rollingLineCount . ")</p>";
            }
            $this->doc .= $sectionContent;

            // No ads on private page


            $isLastSection = $sectionNumber === count($this->sections) - 1;
            if (AdsUtility::showAds(
                $sectionLineCount,
                $currentLineCountSinceLastAd,
                $sectionNumber,
                $adsCounter,
                $isLastSection
            )) {


                // Counter
                $adsCounter += 1;
                $currentLineCountSinceLastAd = 0;

                $attributes = array("name" => AdsUtility::PREFIX_IN_ARTICLE_ADS . $adsCounter);
                $this->doc .= AdsUtility::render($attributes);


            }


        }

        parent::document_end();

    }

    /**
     * Start a table
     *
     * @param int $maxcols maximum number of columns
     * @param int $numrows NOT IMPLEMENTED
     * @param int $pos byte position in the original source
     * @param string|string[]  classes - have to be valid, do not pass unfiltered user input
     */
    function table_open($maxcols = null, $numrows = null, $pos = null, $classes = NULL)
    {
        // initialize the row counter used for classes
        $this->_counter['row_counter'] = 0;
        TableUtility::tableOpen($this, $pos);
    }

    /**
     * https://getbootstrap.com/docs/4.4/content/typography/#inline-text-elements
     */
    public
    function monospace_open()
    {
        $this->doc .= '<mark>';
    }

    public
    function monospace_close()
    {
        $this->doc .= '</mark>';
    }


}
