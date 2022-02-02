<?php



require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * A rendering in XML
 */
class  renderer_plugin_combo_xml extends Doku_Renderer_xhtml
{

    const FORMAT = "xml";

    /**
     * The last two words of the class
     */
    const MODE = 'combo_'.self::FORMAT;


    function getFormat(): string
    {
        return self::FORMAT;
    }

    /*
     * Function that enable to list the plugin in the options for config:renderer_xhtml
     * http://www.dokuwiki.org/config:renderer_xhtml
     * setting in its Configuration Manager.
     */
    public function canRender($format)
    {
        return ($format == 'xml');
    }


    /**
     * Render a heading
     *
     *
     * @param string $text the text to display
     * @param int $level header level
     * @param int $pos byte position in the original source
     */
    function header($text, $level, $pos)
    {

        $this->doc .= "<h$level>$text</h$level>";

    }

    /**
     * This are edit zone section (not HTML/Outline Section)
     * @param int $level
     */
    public function section_open($level)
    {
        $this->doc .= "";
    }

    public function section_close()
    {
        $this->doc .= "";
    }


    public function document_start()
    {
        $this->doc .= '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>';
        $this->doc .= "<document>";
    }

    public function multiplyentity($x, $y): string
    {
        return "$x x $y";
    }


    function document_end()
    {

        $this->doc .= "</document>";

        /**
         * The result can be seen with
         * doku.php?id=somepage&do=export_combo_xml
         *
         * Set the header temporarily for the export.php file
         *
         * The mode in the export is
         */
        $mode = "combo_" . $this->getPluginComponent();
        global $ID;
        p_set_metadata(
            $ID,
            array("format" => array($mode => array("Content-Type" => 'application/xml'))),
            false,
            true // Persistence is needed because there is a cache
        );

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
