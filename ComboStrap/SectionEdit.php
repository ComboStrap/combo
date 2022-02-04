<?php


namespace ComboStrap;

/**
 * Class SectionEdit
 * @package ComboStrap
 * Manage the section edit button
 */
class SectionEdit
{


    const SEC_EDIT_PATTERN = "/" . self::ENTER_HTML_COMMENT . "\s*" . self::SEC_EDIT_PREFIX . "({.*?})\s*" . self::CLOSE_HTML_COMMENT . "/";
    const SEC_EDIT_PREFIX = "COMBO-EDIT";
    const WIKI_ID = "wiki-id";
    const SECTION_ID = "secid";
    const SECTION_NAME = "name";

    const CANONICAL = "support";
    const ENTER_HTML_COMMENT = "<!--";
    const CLOSE_HTML_COMMENT = "-->";


    private static $countersByWikiId = array();

    private $name;


    /**
     * Section constructor.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }


    public static function create($name): SectionEdit
    {
        return new SectionEdit($name);
    }

    /**
     * @throws ExceptionCombo
     */
    public function toTag(): string
    {
        /**
         * The following data are mandatory from:
         * {@link html_secedit_get_button}
         */
        global $ID;
        if ($ID === null) {
            throw new ExceptionCombo("The global ID is not set", self::CANONICAL);
        }
        $id = $this->getNewId($ID);
        $data = [
            self::WIKI_ID => $ID,
            self::SECTION_ID => $id, // id of the edit button
            self::SECTION_NAME => $this->name,
            "target" => "section",
        ];
        return self::SEC_EDIT_PREFIX . PluginUtility::htmlEncode(json_encode($data));
    }

    /**
     * @throws ExceptionCombo
     */
    public function toHtmlComment(): string
    {
        return self::ENTER_HTML_COMMENT . " " . $this->toTag() . " " . self::CLOSE_HTML_COMMENT;
    }

    public function __toString()
    {
        return "Section Edit $this->name";
    }


    public static function replaceAll($html)
    {
        $editFormCallBack = function ($matches) {
            $json = PluginUtility::htmlDecode($matches[1]);
            $data = json_decode($json, true);
            if ($data == NULL) {
                return "";
            }
            $id = $data[self::WIKI_ID];
            $sectionId = $data[self::SECTION_ID];
            $name = $data[self::SECTION_NAME];
            global $INFO;
            return "<div class='edit-combo secedit editbutton_" . $data['target'] .
                " editbutton_" . $sectionId . "'>" .
                html_btn(
                    'secedit',
                    $id,
                    '',
                    array(
                        'do' => 'edit',
                        'summary' => '[' . $name . '] '
                    ),
                    'post',
                    $name) . '</div>';
        };
        return preg_replace_callback(self::SEC_EDIT_PATTERN, $editFormCallBack, $html);
    }

    private function getNewId($wikiId): int
    {
        $counter = self::$countersByWikiId[$wikiId];
        if ($counter === null) {
            // be sure that there is no cache problem from old run
            // if the id has changed, reset
            self::$countersByWikiId = [];
            $counter = 0;
        }
        $counter++;
        self::$countersByWikiId[$wikiId] = $counter;
        return $counter;
    }
}
