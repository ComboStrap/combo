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
    const EDIT_BUTTON_PREFIX = "EDIT";
    const WIKI_ID = "wiki-id";

    const FORM_ID = "hid"; // id to be dokuwiki conform
    const EDIT_MESSAGE = "name"; // name to be dokuwiki conform

    const CANONICAL = "edit-button";
    const ENTER_HTML_COMMENT = "<!--";
    const CLOSE_HTML_COMMENT = "-->";
    const SNIPPET_ID = "edit-button";


    /**
     * The target drive the type of editor
     * As of today, there is two type
     * section and table
     */
    const TARGET_ATTRIBUTE_NAME = "target";
    const TARGET_SECTION_VALUE = "section";
    /**
     * The table does not have an edit form at all
     * It's created by {@link \Doku_Renderer_xhtml::table_close()}
     * They are not printed by default via CSS. Edittable show them by default via Javascript
     */
    const TARGET_TABLE_VALUE = "table";
    public const EDIT_SECTION_TARGET = 'section';
    const RANGE = "range";
    const DOKUWIKI_FORMAT = "dokuwiki";
    const COMBO_FORMAT = "combo";
    const TAG = "edit-button";
    const CLASS_SUFFIX = "edit-button";


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
    private string $target = self::TARGET_SECTION_VALUE;
    /**
     * @var int
     */
    private int $startPosition;

    private ?int $endPosition;
    /**
     * @var string $format - to conform or not to dokuwiki format
     */
    private string $format = self::COMBO_FORMAT;

    /**
     * the id of the heading, ie the id of the section
     * Not really needed, just to be conform with Dokuwiki
     * When the edit button is not for an outline may be null
     */
    private ?string $outlineHeadingId = null;
    /**
     * @var ?int $sectionid - sequence id of the section used only by dokuwiki
     * When the edit button is not for an outline may be null
     */
    private ?int $outlineSectionId = null;


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
        $editButton = EditButton::create($label)
            ->setStartPosition($startPosition)
            ->setEndPosition($endPosition)
            ->setWikiId($wikiId);
        $headingId = $attributes[\syntax_plugin_combo_edit::HEADING_ID];
        if ($headingId !== null) {
            $editButton->setOutlineHeadingId($headingId);
        }
        $sectionId = $attributes[\syntax_plugin_combo_edit::SECTION_ID];
        if ($sectionId !== null) {
            $editButton->setOutlineSectionId($sectionId);
        }
        $format = $attributes[\syntax_plugin_combo_edit::FORMAT];
        if ($format !== null) {
            $editButton->setFormat($format);
        }
        return $editButton;


    }

    public static function deleteAll(string $html)
    {
        // Dokuwiki way is to delete
        // but because they are comment, they are not shown
        // We delete to serve clean page to search engine
        return preg_replace(SEC_EDIT_PATTERN, '', $html);
    }

    public static function replaceOrDeleteAll(string $html_output)
    {
        try {
            return EditButton::replaceAll($html_output);
        } catch (ExceptionNotAuthorized|ExceptionBadState $e) {
            return EditButton::deleteAll($html_output);
        }
    }

    /**
     * See {@link \Doku_Renderer_xhtml::finishSectionEdit()}
     */
    public function toTag(): string
    {

        /**
         * The following data are mandatory from:
         * {@link html_secedit_get_button}
         */
        $wikiId = $this->getWikiId();


        /**
         * We follow the order of Dokuwiki for compatibility purpose
         */
        $data[self::TARGET_ATTRIBUTE_NAME] = $this->target;

        if ($this->format === self::COMBO_FORMAT) {
            /**
             * In the combo edit format, we had the dokuwiki id
             * because the edit button may also be on the secondary slot
             */
            $data[self::WIKI_ID] = $wikiId;
        }
        $data[self::EDIT_MESSAGE] = $this->label;
        if ($this->format === self::COMBO_FORMAT) {
            /**
             * In the combo edit format, we had the dokuwiki id as form id
             * to make it unique on the whole page
             * because the edit button may also be on the secondary slot
             */
            $slotPath = WikiPath::createMarkupPathFromId($wikiId);
            $formId = IdManager::getOrCreate()->generateNewHtmlIdForComponent(self::CANONICAL, $slotPath);
            $data[self::FORM_ID] = $formId;
        } else {
            $data[self::FORM_ID] = $this->getHeadingId();
            $data["codeblockOffset"] = 0; // what is that ?
            $data["secid"] = $this->getSectionId();
        }
        $data[self::RANGE] = $this->getRange();


        return self::EDIT_BUTTON_PREFIX . Html::encode(json_encode($data));
    }

    /**
     *
     * @throws ExceptionBadArgument - if the wiki id could not be found
     * @throws ExceptionNotEnabled
     */
    public function toHtmlComment(): string
    {
        global $ACT;
        if ($ACT === FetcherMarkup::MARKUP_DYNAMIC_EXECUTION_NAME) {
            // ie weblog, they are generated via dynamic markup
            // meaning that there is no button to edit the file
            if (!PluginUtility::isTest()) {
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


    /**
     * @throws ExceptionNotAuthorized - if the user cannot modify the page
     * @throws ExceptionBadState - if the page is a revision page or the HTML is not the output of a page
     */
    public static function replaceAll($html)
    {

        if (!Identity::isWriter()) {
            throw new ExceptionNotAuthorized("Page is not writable by the user");
        }
        /**
         * Delete the edit comment
         *   * if not writable
         *   * or an old revision
         * Original: {@link html_secedit()} {@link html_secedit_get_button()}
         */
        global $INFO;
        if (isset($INFO)) {
            // the page is a revision page
            if ($INFO['rev']) {
                throw new ExceptionBadState("Internal Error: No edit button can be added to a revision page");
            }
        }


        global $ACT;
        if ($ACT !== "show") {
            throw new ExceptionBadState("The HTML is not the rendering of a page (ACT is not show)");
        }

        /**
         * Request based because the button are added only for a user that can write
         */
        $snippetManager = PluginUtility::getSnippetManager();
        $snippetManager->attachCssInternalStylesheet(self::SNIPPET_ID);
        $snippetManager->attachJavascriptFromComponentId(self::SNIPPET_ID);

        /**
         * The callback function on all edit comment
         * @param $matches
         * @return string
         */
        $editFormCallBack = function ($matches) {
            $json = Html::decode($matches[1]);
            $data = json_decode($json, true);

            $target = $data[self::TARGET_ATTRIBUTE_NAME];

            $message = $data[self::EDIT_MESSAGE];
            unset($data[self::EDIT_MESSAGE]);
            if ($message === null || trim($message) === "") {
                $message = "Edit {$target}";
            }

            if ($data === NULL) {
                LogUtility::internalError("No data found in the edit comment", self::CANONICAL);
                return "";
            }
            $wikiId = $data[self::WIKI_ID];
            unset($data[self::WIKI_ID]);
            if ($wikiId === null) {
                try {
                    $page = MarkupPath::createPageFromExecutingId();
                } catch (ExceptionNotFound $e) {
                    LogUtility::internalError("A page id is mandatory for a edit button (no wiki id, no global ID were found). No edit buttons was created then.", self::CANONICAL);
                    return "";
                }
            } else {
                $page = MarkupPath::createMarkupFromId($wikiId);
            }
            $formId = $data[self::FORM_ID];
            unset($data[self::FORM_ID]);
            $data["summary"] = $message;
            try {
                $data['rev'] = $page->getPathObject()->getRevisionOrDefault();
            } catch (ExceptionNotFound $e) {
                LogUtility::internalError("The file ({$page->getPathObject()}) does not exist, we cannot set the last modified time on the edit buttons.", self::CANONICAL);
            }
            $hiddenInputs = "";
            foreach ($data as $key => $val) {
                $inputAttributes = TagAttributes::createEmpty()
                    ->addOutputAttributeValue("name", $key)
                    ->addOutputAttributeValue("value", $val)
                    ->addOutputAttributeValue("type", "hidden");
                $hiddenInputs .= $inputAttributes->toHtmlEmptyTag("input");
            }
            $url = $page->getUrlWhereIdIs(PageUrlType::CONF_VALUE_PAGE_PATH);
            $classPageEdit = StyleUtility::addComboStrapSuffix(self::CLASS_SUFFIX);

            /**
             * Important Note: the first div and the public class is mandatory for the edittable plugin
             * See {@link editbutton.js file}
             */
            $editTableClass = "editbutton_{$target}";
            return <<<EOF
<div class="$classPageEdit $editTableClass">
    <form id="$formId" method="post" action="{$url->toHtmlString()}">
    $hiddenInputs
    <input name="do" type="hidden" value="edit"/>
    <button type="submit" title="$message">
    </button>
    </form>
</div>
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

    public function setStartPosition(int $startPosition): EditButton
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
        if (isset($this->startPosition)) {
            $range = $this->startPosition;
        }
        $range = "$range-";
        if (isset($this->endPosition)) {
            $range = "$range{$this->endPosition}";
        }
        return $range;

    }

    public function toComboCallComboFormat(): Call
    {
        return $this->toComboCall(self::COMBO_FORMAT);
    }

    public function toComboCall($format): Call
    {
        return Call::createComboCall(
            \syntax_plugin_combo_edit::TAG,
            DOKU_LEXER_SPECIAL,
            [
                \syntax_plugin_combo_edit::START_POSITION => $this->startPosition,
                \syntax_plugin_combo_edit::END_POSITION => $this->endPosition,
                \syntax_plugin_combo_edit::LABEL => $this->label,
                \syntax_plugin_combo_edit::FORMAT => $format,
                \syntax_plugin_combo_edit::HEADING_ID => $this->getHeadingId(),
                \syntax_plugin_combo_edit::SECTION_ID => $this->getSectionId(),
                TagAttributes::WIKI_ID => $this->getWikiId()
            ]
        );
    }


    /**
     *
     * @throws ExceptionNotFound - no wiki id found
     */
    private function getWikiId(): string
    {

        $wikiId = $this->wikiId;
        if ($wikiId !== null) {
            return $wikiId;
        }

        return ExecutionContext::getActualOrCreateFromEnv()->getRequestedWikiId();


    }


    public function toComboCallDokuWikiForm(): Call
    {
        return $this->toComboCall(self::DOKUWIKI_FORMAT);
    }

    /** @noinspection PhpReturnValueOfMethodIsNeverUsedInspection */
    private function setFormat($format): EditButton
    {

        if (!in_array($format, [self::DOKUWIKI_FORMAT, self::COMBO_FORMAT])) {
            LogUtility::internalError("The tag format ($format) is not valid", self::CANONICAL);
            return $this;
        }
        $this->format = $format;
        return $this;
    }

    public function setOutlineHeadingId($id): EditButton
    {
        $this->outlineHeadingId = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    private function getHeadingId(): ?string
    {
        return $this->outlineHeadingId;
    }

    private function getSectionId(): ?int
    {
        return $this->outlineSectionId;
    }

    public function setOutlineSectionId(int $sectionSequenceId): EditButton
    {
        $this->outlineSectionId = $sectionSequenceId;
        return $this;
    }

}
