<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\TagAttribute\StyleAttribute;
use Doku_Renderer;
use DokuWiki_Admin_Plugin;
use syntax_plugin_combo_toc;

class Toc extends Metadata
{


    const CANONICAL = syntax_plugin_combo_toc::CANONICAL;
    private ?array $tocData = null;


    public static function createForRequestedPage(): Toc
    {
        return self::createForPage(MarkupPath::createFromRequestedPage());
    }

    public static function getClass(): string
    {
        return StyleAttribute::addComboStrapSuffix(self::CANONICAL);
    }

    /**
     * @throws ExceptionBadArgument - if the TOC is not an array
     * @throws ExceptionNotFound - if the TOC variable was not found
     */
    public static function createFromGlobalVariable(): Toc
    {
        global $TOC;
        if ($TOC === null) {
            throw new ExceptionNotFound("No global TOC variable found");
        }
        return (new Toc())
            ->setValue($TOC);
    }

    public static function createEmpty(): Toc
    {
        return new Toc();
    }


    public function toXhtml(): string
    {

        $this->buildCheck();

        if ($this->tocData === null) {
            return "";
        }

        PluginUtility::getSnippetManager()->attachCssInternalStyleSheet(self::CANONICAL);

        $toc = $this->tocData;

        $tocMinHeads = Site::getTocMinHeadings();
        if (count($toc) < $tocMinHeads) {
            return "";
        }

        /**
         * Adding toc number style
         */
        try {
            $css = Outline::getCssNumberingRulesFor(Outline::TOC_NUMBERING);
            PluginUtility::getSnippetManager()->attachCssInternalStyleSheet(Outline::TOC_NUMBERING, $css);
        } catch (ExceptionNotEnabled $e) {
            // not enabled
        } catch (ExceptionBadSyntax $e) {
            LogUtility::error("The toc numbering type was unknown", self::CANONICAL);
        }

        /**
         * Creating the html
         */
        global $lang;

        // To keep track of the HTML level (levels may be badly encoded)
        $htmlLevel = 0;
        $previousLevel = 0;
        $topTocLevel = Site::getTopTocLevel();
        $ulMarkup = "";
        foreach ($toc as $tocItem) {

            $actualLevel = $tocItem["level"];

            /**
             * Skipping to the first top level
             */
            if ($actualLevel < $topTocLevel) {
                $previousLevel = $actualLevel;
                continue;
            }

            /**
             * Closing
             */
            $levelDiff = $previousLevel - $actualLevel;
            switch (true) {
                case $levelDiff === 0 && (!empty($ulMarkup)):
                    /**
                     * Same level
                     */
                    $ulMarkup .= "</li>";
                    break;
                case ($actualLevel < $previousLevel && !empty($ulMarkup)):
                    /**
                     * One or multiple level up
                     * (from 4 to 2)
                     */
                    $htmlLevel += $levelDiff;
                    $ulMarkup .= str_repeat("</li></ul>", $levelDiff);
                    $ulMarkup .= "</li>";
                    break;
                default:
                    /**
                     * One level down
                     * (We can't go multiple at once)
                     */
                    $htmlLevel -= 1;
                    $ulMarkup .= "<ul>";
                    break;
            }

            $href = $tocItem['link'];
            $label = $tocItem['title'];
            $tocLevelClass = StyleAttribute::addComboStrapSuffix("toc-level-$actualLevel");
            $ulMarkup .= "<li><a href=\"$href\" class=\"$tocLevelClass\">$label</a>";
            /**
             * Close
             */
            $previousLevel = $actualLevel;
        }
        // grand closing
        $ulMarkup .= str_repeat("</li></ul>", abs($htmlLevel));
        $tocHeaderLang = $lang['toc'];
        $tocHeaderClass = StyleAttribute::addComboStrapSuffix("toc-header");
        return <<<EOF
<p class="$tocHeaderClass">$tocHeaderLang</p>
$ulMarkup
EOF;


    }


    /**
     * @param Doku_Renderer $renderer
     * @return bool if the toc need to be shown
     *
     * From {@link Doku_Renderer::notoc()}
     * $this->info['toc'] = false;
     * when
     * ~~NOTOC~~
     */
    public static function showToc(Doku_Renderer $renderer): bool
    {

        global $ACT;

        /**
         * Search page, no toc
         */
        if ($ACT === 'search') {
            return false;
        }

        /**
         * If this is another template such as Dokuwiki, we get two TOC.
         */
        if (!Site::isStrapTemplate()) {
            return false;
        }

        /**
         * On the admin page
         */
        if ($ACT === 'admin') {

            global $INPUT;
            $plugin = null;
            $class = $INPUT->str('page');
            if (!empty($class)) {

                $pluginList = plugin_list('admin');

                if (in_array($class, $pluginList)) {
                    // attempt to load the plugin
                    /** @var $plugin DokuWiki_Admin_Plugin */
                    $plugin = plugin_load('admin', $class);
                }

                if ($plugin !== null) {
                    global $TOC;
                    if (!is_array($TOC)) $TOC = $plugin->getTOC(); //if TOC wasn't requested yet
                    if (!is_array($TOC)) {
                        return false;
                    } else {
                        return true;
                    }

                }

            }

        }

        // return it if set otherwise return true
        return $renderer->info['toc'] ?? true;

    }

    public function shouldTocBePrinted(): bool
    {
        global $conf;
        return $conf['tocminheads'] && count($this->tocData) >= $conf['tocminheads'];
    }

    public static function createForPage($page): Toc
    {
        return (new Toc())
            ->setResource($page);
    }


    /**
     * @throws ExceptionBadArgument
     */
    public function setValue($value): Toc
    {
        if (!is_array($value)) {
            throw new ExceptionBadArgument("The toc value ($value) is not an array");
        }
        $this->tocData = $value;
        /**
         * We don't set the global TOC because
         * if the global TOC is set {@link tpl_admin()}, will not
         * ask the toc to the admin plugin
         */
//        global $TOC;
//        $TOC = $value;
        return $this;
    }

    public function valueIsNotNull(): bool
    {
        return $this->tocData !== null;
    }

    static     public function getDataType(): string
    {
        return DataType::ARRAY_VALUE;
    }

    static public function getDescription(): string
    {
        return "Table of Contents";
    }

    static public function getLabel(): string
    {
        return "The table of content for the page";
    }

    public static function getName(): string
    {
        return "toc";
    }

    static public function getPersistenceType(): string
    {
        return Metadata::DERIVED_METADATA;
    }

    static public function isMutable(): bool
    {
        return true;
    }

    public function setFromStoreValueWithoutException($value): Metadata
    {
        $this->tocData = $value;
        return $this;
        // We can't modify the toc of dokuwiki
        // This data shows how to get the table of content from dokuwiki
        // $description = $metaDataStore->getCurrentFromName("description");
        // if($description!==null) {
        //    $this->tocData = $description["tableofcontents"];
        // }

    }

    /**
     * @return array
     * @throws ExceptionNotFound
     */
    public function getValue(): array
    {
        $this->buildCheck();
        if ($this->tocData === null) {
            throw new ExceptionNotFound("No toc");
        }
        return $this->tocData;
    }

    public function getDefaultValue(): array
    {
        return [];
    }
}
