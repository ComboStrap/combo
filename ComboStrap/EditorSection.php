<?php


namespace ComboStrap;

/**
 * Class SectionEdit
 * @package ComboStrap
 * Manage the section edit button
 */
class EditorSection
{


    const SEC_EDIT_PATTERN = "/" . self::ENTER_HTML_COMMENT . "\s*" . self::SEC_EDIT_PREFIX . "({.*?})\s*" . self::CLOSE_HTML_COMMENT . "/";
    const SEC_EDIT_PREFIX = "COMBO-EDIT";
    const WIKI_ID = "wiki-id";

    const EDIT_EDIT_ID = "id";
    const EDIT_MESSAGE = "message";

    const CANONICAL = "editor-section";
    const ENTER_HTML_COMMENT = "<!--";
    const CLOSE_HTML_COMMENT = "-->";
    const CLASS_PAGE_EDIT = "page-edit-combo";
    const SNIPPET_ID = "page-edit";
    public const PAGE_EDIT_BUTTON_CONF = "page-edit-button";




    private $name;
    /**
     * @var string
     */
    private $id;


    /**
     * Section constructor.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }


    public static function create($name): EditorSection
    {
        return new EditorSection($name);
    }

    /**
     * @throws ExceptionCompile
     */
    public function toTag(): string
    {
        $pageEditButton = PluginUtility::getConfValue(EditorSection::PAGE_EDIT_BUTTON_CONF, 1);
        if ($pageEditButton !== 1) {
            return "";
        }

        /**
         * The following data are mandatory from:
         * {@link html_secedit_get_button}
         */
        $markupId = $this->id;
        if($markupId===null) {
            global $ID;
            if ($ID === null) {
                throw new ExceptionCompile("A markup id could was not set", self::CANONICAL);
            }
        }
        $slotPath = DokuPath::createPagePathFromId($markupId);
        $htmlId = \IdManager::getOrCreate()->generateNewIdForComponent(self::CANONICAL, $slotPath);
        $data = [
            self::WIKI_ID => $markupId,
            self::EDIT_MESSAGE => $this->name,
            self::EDIT_EDIT_ID => $htmlId, // id of the form
            // "target" => "section", // this is the default
        ];
        return self::SEC_EDIT_PREFIX . json_encode($data);
    }

    /**
     * @throws ExceptionCompile
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
        PluginUtility::getSnippetManager()->attachCssInternalStylesheetForRequest(self::SNIPPET_ID);

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



    public function setId(string $id): EditorSection
    {
        $this->id = $id;
        return $this;
    }
}
