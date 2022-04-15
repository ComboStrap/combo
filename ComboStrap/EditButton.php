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
    const RANGE = "range";


    private $label;
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
     * @var int
     */
    private $startPosition;
    /**
     * @var int
     */
    private $endPosition;


    /**
     * Section constructor.
     */
    public function __construct($label)
    {
        $this->label = $label;
    }


    public static function create($label): EditButton
    {
        return new EditButton($label);
    }

    public static function createFromCallStackArray($attributes): EditButton
    {
        $label = $attributes[\syntax_plugin_combo_edit::LABEL];
        $startPosition = $attributes[\syntax_plugin_combo_edit::START_POSITION];
        $endPosition = $attributes[\syntax_plugin_combo_edit::END_POSITION];
        $wikiId = $attributes[TagAttributes::WIKI_ID];
        return EditButton::create($label)
            ->setStartPosition($startPosition)
            ->setEndPosition($endPosition)
            ->setWikiId($wikiId);

    }

    /**
     *
     * @throws ExceptionBadArgument
     * @throws ExceptionNotEnabled
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
            throw new ExceptionNotEnabled("Edit button functionality is not enabled");
        }

        /**
         * The following data are mandatory from:
         * {@link html_secedit_get_button}
         */
        $wikiId = $this->getWikiId();
        $slotPath = DokuPath::createPagePathFromId($wikiId);
        $formId = \IdManager::getOrCreate()->generateNewIdForComponent(self::CANONICAL, $slotPath);
        $data = [
            self::WIKI_ID => $wikiId,
            self::EDIT_MESSAGE => $this->label,
            self::FORM_ID => $formId,
            self::TARGET_ATTRIBUTE_NAME => $this->target,
            self::RANGE => $this->getRange()
        ];
        return self::EDIT_BUTTON_PREFIX . json_encode($data);
    }

    /**
     *
     * @throws ExceptionBadArgument - if the wiki id could not be found
     * @throws ExceptionNotEnabled
     */
    public function toHtmlComment(): string
    {
        global $ACT;
        if ($ACT === RenderUtility::DYNAMIC_RENDERING) {
            // ie weblog, they are generated via dynamic markup
            // meaning that there is no button to edit the file
            if(!PluginUtility::isTest()) {
                return "";
            }
        }
        /**
         * We don't encode there is only internal information
         * and this is easier to see / debug the output
         */
        return self::ENTER_HTML_COMMENT . " " . $this->toTag() . " " . self::CLOSE_HTML_COMMENT;
    }

    public function __toString()
    {
        return "Section Edit $this->label";
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
            $json = Html::decode($matches[1]);
            $data = json_decode($json, true);
            if ($data === NULL) {
                return "";
            }
            $wikiId = $data[self::WIKI_ID];
            if ($wikiId === null) {
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

    public function setStartPosition(?int $startPosition): EditButton
    {
        $this->startPosition = $startPosition;
        return $this;
    }

    public function setEndPosition(?int $endPosition): EditButton
    {
        $this->endPosition = $endPosition;
        return $this;
    }

    /**
     * @return string the file character position range of the section to edit
     */
    private function getRange(): string
    {
        $range = "";
        if ($this->startPosition !== null) {
            $range = $this->startPosition;
        }
        $range = "$range-";
        if ($this->endPosition !== null) {
            $range = "$range{$this->endPosition}";
        }
        return $range;

    }

    public function toComboCall(): Call
    {
        return Call::createComboCall(
            \syntax_plugin_combo_edit::TAG,
            DOKU_LEXER_SPECIAL,
            [
                \syntax_plugin_combo_edit::START_POSITION => $this->startPosition,
                \syntax_plugin_combo_edit::END_POSITION => $this->endPosition,
                \syntax_plugin_combo_edit::LABEL => $this->label,
                TagAttributes::WIKI_ID => $this->getWikiId()
            ]
        );
    }

    /**
     *
     */
    private function getWikiId(): string
    {

        $wikiId = $this->wikiId;
        if ($wikiId !== null) {
            return $wikiId;
        }
        return PluginUtility::getCurrentSlotId();

    }
}