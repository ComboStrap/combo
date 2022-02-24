<?php


namespace ComboStrap;

/**
 * Class SectionEdit
 * @package ComboStrap
 * Manage the section edit button
 */
class PageEdit
{


    const SEC_EDIT_PATTERN = "/" . self::ENTER_HTML_COMMENT . "\s*" . self::SEC_EDIT_PREFIX . "({.*?})\s*" . self::CLOSE_HTML_COMMENT . "/";
    const SEC_EDIT_PREFIX = "COMBO-EDIT";
    const WIKI_ID = "wiki-id";

    const EDIT_EDIT_ID = "id";
    const EDIT_MESSAGE = "message";

    const CANONICAL = "support";
    const ENTER_HTML_COMMENT = "<!--";
    const CLOSE_HTML_COMMENT = "-->";
    const CLASS_PAGE_EDIT = "page-edit-combo";
    const SNIPPET_ID = "page-edit";
    public const PAGE_EDIT_BUTTON_CONF = "page-edit-button";


    private static $countersByWikiId = array();

    private $name;


    /**
     * Section constructor.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }


    public static function create($name): PageEdit
    {
        return new PageEdit($name);
    }

    /**
     * @throws ExceptionCombo
     */
    public function toTag(): string
    {
        $pageEditButton = PluginUtility::getConfValue(PageEdit::PAGE_EDIT_BUTTON_CONF, 1);
        if ($pageEditButton !== 1) {
            return "";
        }

        /**
         * The following data are mandatory from:
         * {@link html_secedit_get_button}
         */
        global $ID;
        if ($ID === null) {
            throw new ExceptionCombo("The global ID is not set", self::CANONICAL);
        }
        $id = $this->getNewFormId($ID);
        $data = [
            self::WIKI_ID => $ID,
            self::EDIT_MESSAGE => $this->name,
            self::EDIT_EDIT_ID => $id, // id of the form
            // "target" => "section", // this is the default
        ];
        return self::SEC_EDIT_PREFIX . json_encode($data);
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

        /**
         * Delete the edit comment
         *   * if not writable
         *   * or an old revision
         * Original: {@link html_secedit()}
         */
        global $INFO;
        if ((isset($INFO) && !$INFO['writable']) || (isset($INFO) && $INFO['rev'])) {
            return preg_replace(SEC_EDIT_PATTERN, '', $html);
        }

        /**
         * Request based because the button are added only if you can write
         */
        PluginUtility::getSnippetManager()->attachCssSnippetForRequest(self::SNIPPET_ID);

        /**
         * The callback function on all edit comment
         * @param $matches
         * @return string
         */
        $editFormCallBack = function ($matches) {
            $json = PluginUtility::htmlDecode($matches[1]);
            $data = json_decode($json, true);
            if ($data == NULL) {
                return "";
            }
            $wikiId = $data[self::WIKI_ID];
            unset($data[self::WIKI_ID]);
            $editId = $data[self::EDIT_EDIT_ID];
            unset($data[self::EDIT_EDIT_ID]);
            $message = $data[self::EDIT_MESSAGE];
            unset($data[self::EDIT_MESSAGE]);
            $data["summary"] = $message;
            $page = Page::createPageFromId($wikiId);
            $inputs = "";
            foreach ($data as $key => $val) {
                $inputAttributes = TagAttributes::createEmpty()
                    ->addOutputAttributeValue("name", $key)
                    ->addOutputAttributeValue("value", $val)
                    ->addOutputAttributeValue("type", "hidden");
                $inputs .= $inputAttributes->toHtmlEmptyTag("input");
            }
            $url = $page->getUrl(PageUrlType::CONF_VALUE_PAGE_PATH);
            $wikiIdHtmlClassForm = str_replace(":", "-", $wikiId);
            $classPageEdit = self::CLASS_PAGE_EDIT;
            return <<<EOF
<form id="edit-combo-$wikiIdHtmlClassForm-$editId" class="$classPageEdit" method="post" action="{$url}">
$inputs
<input name="do" type="hidden" value="edit"/>
<button type="submit" title="Edit the slot $wikiId">
</button>
</form>
EOF;
        };

        /**
         * The replacement
         */
        return preg_replace_callback(self::SEC_EDIT_PATTERN, $editFormCallBack, $html);
    }

    private function getNewFormId($wikiId): int
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
