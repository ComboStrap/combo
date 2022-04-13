<?php


namespace ComboStrap;

/**
 * Class SectionEdit
 * @package ComboStrap
 * Manage the edit button
 * (ie add HTML comment that are parsed into forms
 * for editor user)
 */
class EditButton
{


    const SEC_EDIT_PATTERN = "/" . self::ENTER_HTML_COMMENT . "\s*" . self::EDIT_BUTTON_PREFIX . "({.*?})\s*" . self::CLOSE_HTML_COMMENT . "/";
    const EDIT_BUTTON_PREFIX = "COMBO-EDIT";
    const WIKI_ID = "wiki-id";

    const FORM_ID = "form-id";
    const EDIT_MESSAGE = "message";

    const CANONICAL = "edit-button";
    const ENTER_HTML_COMMENT = "<!--";
    const CLOSE_HTML_COMMENT = "-->";
    const CLASS_EDIT_BUTTON = "edit-button-combo";
    const SNIPPET_ID = "edit-button";

    /**
     * Uses internally to delete its usage by default
     * on test
     */
    public const EDIT_BUTTON_ENABLED_INTERNAL_CONF = "edit-button-enabled";


    /**
     * The target drive the type of editor
     * As of today, there is two type
     * section and table
     */
    const TARGET_ATTRIBUTE_NAME = "target";
    const TARGET_SECTION_VALUE = "section";
    const TARGET_TABLE_VALUE = "table"; // not yet used
    public const EDIT_SECTION_TARGET = 'section';


    private $name;
    /**
     * @var string
     */
    private $wikiId;

    /**
     * Edit type
     * @var string
     * This is the default
     */
    private $target = self::TARGET_SECTION_VALUE;


    /**
     * Section constructor.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }


    public static function create($name): EditButton
    {
        return new EditButton($name);
    }

    /**
     * @param \Doku_Renderer_xhtml $renderer
     * @param $position
     * @param $name
     */
    public static function startSection(\Doku_Renderer_xhtml $renderer, $position, $name)
    {


        if (empty($position)) {
            LogUtility::msg("The position for a start section should not be empty", LogUtility::LVL_MSG_ERROR, "support");
        }
        if (empty($name)) {
            LogUtility::msg("The name for a start section should not be empty", LogUtility::LVL_MSG_ERROR, "support");
        }

        /**
         * New Dokuwiki Version
         * for DokuWiki Greebo and more recent versions
         */
        if (defined('SEC_EDIT_PATTERN')) {
            $renderer->startSectionEdit($position, array('target' => self::EDIT_SECTION_TARGET, 'name' => $name));
        } else {
            /**
             * Old version
             */
            /** @noinspection PhpParamsInspection */
            $renderer->startSectionEdit($position, self::EDIT_SECTION_TARGET, $name);
        }
    }

    /**
     * @throws ExceptionCompile
     */
    public function toTag(): string
    {

        /**
         * This is an internal configuration used
         * to disable this functionality in test by default
         * This is not a public configuration
         */
        $pageEditButton = PluginUtility::getConfValue(EditButton::EDIT_BUTTON_ENABLED_INTERNAL_CONF, 1);
        if ($pageEditButton !== 1) {
            return "";
        }

        /**
         * The following data are mandatory from:
         * {@link html_secedit_get_button}
         */
        $wikiId = $this->wikiId;
        if ($wikiId === null) {
            global $ID;
            if ($ID === null) {
                throw new ExceptionCompile("A wiki id was not set nor found", self::CANONICAL);
            }
        }
        $slotPath = DokuPath::createPagePathFromId($wikiId);
        $formId = \IdManager::getOrCreate()->generateNewIdForComponent(self::CANONICAL, $slotPath);
        $data = [
            self::WIKI_ID => $wikiId,
            self::EDIT_MESSAGE => $this->name,
            self::FORM_ID => $formId,
            self::TARGET_ATTRIBUTE_NAME => $this->target,
        ];
        return self::EDIT_BUTTON_PREFIX . json_encode($data);
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
        $writable =
            (
                isset($INFO)
                && $INFO['writable'] // true if writable See https://www.dokuwiki.org/devel:environment#info
            )
            ||
            (
                isset($INFO)
                && !$INFO['rev'] // the page is not a revision page
            );
        if (!$writable) {
            return preg_replace(SEC_EDIT_PATTERN, '', $html);
        }

        /**
         * Request based because the button are added only for a user that can write
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
            if ($data === NULL) {
                return "";
            }
            $wikiId = $data[self::WIKI_ID];
            if($wikiId===null){
                LogUtility::error("A wiki id should be present to create an edit button", self::CANONICAL);
                return "";
            }
            unset($data[self::WIKI_ID]);
            $formId = $data[self::FORM_ID];
            unset($data[self::FORM_ID]);
            $message = $data[self::EDIT_MESSAGE];
            unset($data[self::EDIT_MESSAGE]);
            $data["summary"] = $message;
            $page = Page::createPageFromId($wikiId);
            $hiddenInputs = "";
            foreach ($data as $key => $val) {
                $inputAttributes = TagAttributes::createEmpty()
                    ->addOutputAttributeValue("name", $key)
                    ->addOutputAttributeValue("value", $val)
                    ->addOutputAttributeValue("type", "hidden");
                $hiddenInputs .= $inputAttributes->toHtmlEmptyTag("input");
            }
            $url = $page->getUrl(PageUrlType::CONF_VALUE_PAGE_PATH);
            $classPageEdit = self::CLASS_EDIT_BUTTON;
            return <<<EOF
<form id="$formId" class="$classPageEdit" method="post" action="{$url}">
$hiddenInputs
<input name="do" type="hidden" value="edit"/>
<button type="submit" title="$message">
</button>
</form>
EOF;
        };

        /**
         * The replacement
         */
        return preg_replace_callback(self::SEC_EDIT_PATTERN, $editFormCallBack, $html);
    }


    public function setWikiId(string $id): EditButton
    {
        $this->wikiId = $id;
        return $this;
    }

    /**
     * Page / Section edit
     * (This is known as the target for dokuwiki)
     * @param string $target
     * @return $this
     *
     */
    public function setTarget(string $target): EditButton
    {
        $this->target = $target;
        return $this;
    }
}
