<?php


use ComboStrap\Analytics;
use ComboStrap\LinkUtility;
use ComboStrap\LogUtility;
use ComboStrap\LowQualityPage;
use ComboStrap\Sqlite;
use ComboStrap\Text;
use ComboStrap\UrlCanonical;
use dokuwiki\ChangeLog\PageChangeLog;

require_once(__DIR__ . '/../class/Text.php');
require_once(__DIR__ . '/../class/LowQualityPage.php');
require_once(__DIR__ . '/../class/Analytics.php');


/**
 * A analysis Renderer that exports stats/quality/metadata in a json format
 * You can export the data with
 * doku.php?id=somepage&do=export_combo_analytics
 */
class renderer_plugin_combo_analytics extends Doku_Renderer
{
    const DATE_CREATED = 'date_created';
    const PLAINTEXT = 'formatted';
    const RESULT = "result";
    const DESCRIPTION = "description";
    const PASSED = "Passed";
    const FAILED = "Failed";
    const FIXME = 'fixme';

    /**
     * Rules key
     */
    const RULE_WORDS_MINIMAL = 'words_min';
    const RULE_OUTLINE_STRUCTURE = "outline_structure";
    const RULE_INTERNAL_BACKLINKS_MIN = 'internal_backlinks_min';
    const RULE_WORDS_MAXIMAL = "words_max";
    const RULE_AVERAGE_WORDS_BY_SECTION_MIN = 'words_by_section_avg_min';
    const RULE_AVERAGE_WORDS_BY_SECTION_MAX = 'words_by_section_avg_max';
    const RULE_INTERNAL_LINKS_MIN = 'internal_links_min';
    const RULE_INTERNAL_BROKEN_LINKS_MAX = 'internal_links_broken_max';
    const RULE_DESCRIPTION_PRESENT = 'description_present';
    const RULE_FIXME = "fixme_min";
    const RULE_TITLE_PRESENT = "title_present";
    const RULE_CANONICAL_PRESENT = "canonical_present";

    /**
     * The default man
     */
    const CONF_MANDATORY_QUALITY_RULES_DEFAULT_VALUE = [
        self::RULE_WORDS_MINIMAL,
        self::RULE_INTERNAL_BACKLINKS_MIN,
        self::RULE_INTERNAL_LINKS_MIN
    ];
    const CONF_MANDATORY_QUALITY_RULES = "mandatoryQualityRules";

    /**
     * Quality Score factors
     * They are used to calculate the score
     */
    const CONF_QUALITY_SCORE_INTERNAL_BACKLINK_FACTOR = 'qualityScoreInternalBacklinksFactor';
    const CONF_QUALITY_SCORE_INTERNAL_LINK_FACTOR = 'qualityScoreInternalLinksFactor';
    const CONF_QUALITY_SCORE_TITLE_PRESENT = 'qualityScoreTitlePresent';
    const CONF_QUALITY_SCORE_CORRECT_HEADER_STRUCTURE = 'qualityScoreCorrectOutline';
    const CONF_QUALITY_SCORE_CORRECT_CONTENT = 'qualityScoreCorrectContentLength';
    const CONF_QUALITY_SCORE_NO_FIXME = 'qualityScoreNoFixMe';
    const CONF_QUALITY_SCORE_CORRECT_WORD_SECTION_AVERAGE = 'qualityScoreCorrectWordSectionAvg';
    const CONF_QUALITY_SCORE_INTERNAL_LINK_BROKEN_FACTOR = 'qualityScoreNoBrokenLinks';
    const CONF_QUALITY_SCORE_CHANGES_FACTOR = 'qualityScoreChangesFactor';
    const CONF_QUALITY_SCORE_DESCRIPTION_PRESENT = 'qualityScoreDescriptionPresent';
    const CONF_QUALITY_SCORE_CANONICAL_PRESENT = 'qualityScoreCanonicalPresent';


    /**
     * The processing data
     * that should be {@link  renderer_plugin_combo_analysis::reset()}
     */
    public $stats = array(); // the stats
    protected $metadata = array(); // the metadata
    protected $headerId = 0; // the id of the header on the page (first, second, ...)

    /**
     * Don't known this variable ?
     */
    protected $quotelevel = 0;
    protected $formattingBracket = 0;
    protected $tableopen = false;
    private $plainTextId = 0;


    /**
     * Here the score is calculated
     */
    public function document_end() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        /**
         * The metadata
         */
        global $ID;
        $meta = p_get_metadata($ID);

        /**
         * Word and chars count
         * The word count does not take into account
         * words with non-words characters such as < =
         * Therefore the node and attribute are not taken in the count
         */
        $text = rawWiki($ID);
        $this->stats[Analytics::CHARS_COUNT] = strlen($text);
        $this->stats[Analytics::WORDS_COUNT] = Text::getWordCount($text);

        /**
         * The exported object
         */
        $statExport = $this->stats;


        /**
         * Internal link distance summary calculation
         */
        if (array_key_exists(Analytics::INTERNAL_LINK_DISTANCE, $statExport)) {
            $linkLengths = $statExport[Analytics::INTERNAL_LINK_DISTANCE];
            unset($statExport[Analytics::INTERNAL_LINK_DISTANCE]);
            $countBacklinks = count($linkLengths);
            $statExport[Analytics::INTERNAL_LINK_DISTANCE]['avg'] = null;
            $statExport[Analytics::INTERNAL_LINK_DISTANCE]['max'] = null;
            $statExport[Analytics::INTERNAL_LINK_DISTANCE]['min'] = null;
            if ($countBacklinks > 0) {
                $statExport[Analytics::INTERNAL_LINK_DISTANCE]['avg'] = array_sum($linkLengths) / $countBacklinks;
                $statExport[Analytics::INTERNAL_LINK_DISTANCE]['max'] = max($linkLengths);
                $statExport[Analytics::INTERNAL_LINK_DISTANCE]['min'] = min($linkLengths);
            }
        }

        /**
         * Quality Report / Rules
         */
        // The array that hold the results of the quality rules
        $ruleResults = array();
        // The array that hold the quality score details
        $qualityScores = array();


        /**
         * No fixme
         */
        $fixmeCount = $this->stats[self::FIXME];
        $statExport[self::FIXME] = $fixmeCount == null ? 0 : $fixmeCount;
        if ($fixmeCount != 0) {
            $ruleResults[self::RULE_FIXME] = self::FAILED;
            $qualityScores['no_' . self::FIXME] = 0;
        } else {
            $ruleResults[self::RULE_FIXME] = self::PASSED;
            $qualityScores['no_' . self::FIXME] = $this->getConf(self::CONF_QUALITY_SCORE_NO_FIXME, 1);;
        }

        /**
         * A title should be present
         */
        if (empty($this->metadata[Analytics::TITLE])) {
            $ruleResults[self::RULE_TITLE_PRESENT] = self::FAILED;
            $ruleInfo[self::RULE_TITLE_PRESENT] = "A title is not present in the frontmatter";
            $this->metadata[Analytics::TITLE] = $meta[Analytics::TITLE];
            $qualityScores[self::RULE_TITLE_PRESENT] = 0;
        } else {
            $qualityScores[self::RULE_TITLE_PRESENT] = $this->getConf(self::CONF_QUALITY_SCORE_TITLE_PRESENT, 10);;
            $ruleResults[self::RULE_TITLE_PRESENT] = self::PASSED;
        }

        /**
         * A description should be present
         */
        if (empty($this->metadata[self::DESCRIPTION])) {
            $ruleResults[self::RULE_DESCRIPTION_PRESENT] = self::FAILED;
            $ruleInfo[self::RULE_CANONICAL_PRESENT] = "A description is not present in the frontmatter";
            $this->metadata[self::DESCRIPTION] = $meta[self::DESCRIPTION]["abstract"];
            $qualityScores[self::RULE_DESCRIPTION_PRESENT] = 0;
        } else {
            $qualityScores[self::RULE_DESCRIPTION_PRESENT] = $this->getConf(self::CONF_QUALITY_SCORE_DESCRIPTION_PRESENT, 8);;
            $ruleResults[self::RULE_DESCRIPTION_PRESENT] = self::PASSED;
        }

        /**
         * A canonical should be present
         */
        if (empty($this->metadata[UrlCanonical::CANONICAL_PROPERTY])) {
            $qualityScores[self::RULE_CANONICAL_PRESENT] = 0;
            $ruleResults[self::RULE_CANONICAL_PRESENT] = self::FAILED;
            $ruleInfo[self::RULE_CANONICAL_PRESENT] = "A canonical is not present in the frontmatter";
        } else {
            $qualityScores[self::RULE_CANONICAL_PRESENT] = $this->getConf(self::CONF_QUALITY_SCORE_CANONICAL_PRESENT, 5);;
            $ruleResults[self::RULE_CANONICAL_PRESENT] = self::PASSED;
        }

        /**
         * Outline / Header structure
         */
        $treeError = 0;
        $headersCount = 0;
        if (array_key_exists(Analytics::HEADER_POSITION, $this->stats)) {
            $headersCount = count($this->stats[Analytics::HEADER_POSITION]);
            unset($statExport[Analytics::HEADER_POSITION]);
            for ($i = 1; $i < $headersCount; $i++) {
                $currentHeaderLevel = $this->stats['header_struct'][$i];
                $previousHeaderLevel = $this->stats['header_struct'][$i - 1];
                if ($currentHeaderLevel - $previousHeaderLevel > 1) {
                    $treeError += 1;
                    $ruleInfo[self::RULE_OUTLINE_STRUCTURE] = "The " . $i . " header (h" . $currentHeaderLevel . ") has a level bigger than its precedent (" . $previousHeaderLevel . ")";
                }
            }
        }
        if ($treeError > 0 || $headersCount == 0) {
            $qualityScores['correct_outline'] = 0;
            $ruleResults[self::RULE_OUTLINE_STRUCTURE] = self::FAILED;
            if ($headersCount==0){
                $ruleInfo[self::RULE_OUTLINE_STRUCTURE] = "There is no header";
            }
        } else {
            $qualityScores['correct_outline'] = $this->getConf(self::CONF_QUALITY_SCORE_CORRECT_HEADER_STRUCTURE, 3);
            $ruleResults[self::RULE_OUTLINE_STRUCTURE] = self::PASSED;
        }


        /**
         * Document length
         */
        $minimalWordCount = 50;
        $maximalWordCount = 1500;
        $correctContentLength = true;
        if ($this->stats[Analytics::WORDS_COUNT] < $minimalWordCount) {
            $ruleResults[self::RULE_WORDS_MINIMAL] = self::FAILED;
            $correctContentLength = false;
            $ruleInfo[self::RULE_WORDS_MINIMAL] = "The number of words is less than {$minimalWordCount}";
        } else {
            $ruleResults[self::RULE_WORDS_MINIMAL] = self::PASSED;
        }
        if ($this->stats[Analytics::WORDS_COUNT] > $maximalWordCount) {
            $ruleResults[self::RULE_WORDS_MAXIMAL] = self::FAILED;
            $ruleInfo[self::RULE_WORDS_MAXIMAL] = "The number of words is more than {$maximalWordCount}";
            $correctContentLength = false;
        } else {
            $ruleResults[self::RULE_WORDS_MAXIMAL] = self::PASSED;
        }
        if ($correctContentLength) {
            $qualityScores['correct_content_length'] = $this->getConf(self::CONF_QUALITY_SCORE_CORRECT_CONTENT, 10);
        } else {
            $qualityScores['correct_content_length'] = 0;
        }


        /**
         * Average Number of words by header section to text ratio
         */
        $headers = $this->stats[Analytics::HEADERS_COUNT];
        if ($headers != null) {
            $headerCount = array_sum($headers);
            $headerCount--; // h1 is supposed to have no words
            if ($headerCount > 0) {

                $avgWordsCountBySection = round($this->stats[Analytics::WORDS_COUNT] / $headerCount);
                $statExport['word_section_count']['avg'] = $avgWordsCountBySection;

                /**
                 * Min words by header section
                 */
                $wordsByHeaderMin = 20;
                /**
                 * Max words by header section
                 */
                $wordsByHeaderMax = 300;
                $correctAverageWordsBySection = true;
                if ($avgWordsCountBySection < $wordsByHeaderMin) {
                    $ruleResults[self::RULE_AVERAGE_WORDS_BY_SECTION_MIN] = self::FAILED;
                    $correctAverageWordsBySection = false;
                    $ruleInfo[self::RULE_AVERAGE_WORDS_BY_SECTION_MAX] = "The number of words by section is less than {$wordsByHeaderMin}";
                } else {
                    $ruleResults[self::RULE_AVERAGE_WORDS_BY_SECTION_MIN] = self::PASSED;
                }
                if ($avgWordsCountBySection > $wordsByHeaderMax) {
                    $ruleResults[self::RULE_AVERAGE_WORDS_BY_SECTION_MAX] = self::FAILED;
                    $correctAverageWordsBySection = false;
                    $ruleInfo[self::RULE_AVERAGE_WORDS_BY_SECTION_MAX] = "The number of words by section is more than {$wordsByHeaderMax}";
                } else {
                    $ruleResults[self::RULE_AVERAGE_WORDS_BY_SECTION_MAX] = self::PASSED;
                }
                if ($correctAverageWordsBySection) {
                    $qualityScores['correct_word_avg_by_section'] = $this->getConf(self::CONF_QUALITY_SCORE_CORRECT_WORD_SECTION_AVERAGE, 10);
                } else {
                    $qualityScores['correct_word_avg_by_section'] = 0;
                }

            }
        }

        /**
         * Internal Backlinks rule
         *
         * If a page is a low quality page, if the process run
         * anonymous, we will not see all {@link ft_backlinks()}
         * we use then the index directly to avoid confusion
         */
        $backlinks = idx_get_indexer()->lookupKey('relation_references', $ID);
        $countBacklinks = count($backlinks);
        $statExport[Analytics::INTERNAL_BACKLINKS_COUNT] = $countBacklinks;
        if ($countBacklinks == 0) {
            $qualityScores[Analytics::INTERNAL_BACKLINKS_COUNT] = 0;
            $ruleResults[self::RULE_INTERNAL_BACKLINKS_MIN] = self::FAILED;
            $ruleInfo[self::RULE_INTERNAL_BACKLINKS_MIN] = "There is no backlinks";
        } else {
            $qualityScores[Analytics::INTERNAL_BACKLINKS_COUNT] = $countBacklinks * $this->getConf(self::CONF_QUALITY_SCORE_INTERNAL_BACKLINK_FACTOR, 1);
            $ruleResults[self::RULE_INTERNAL_BACKLINKS_MIN] = self::PASSED;
        }

        /**
         * Internal links
         */
        $internalLinksCount = $this->stats[Analytics::INTERNAL_LINKS_COUNT];
        if ($internalLinksCount == 0) {
            $qualityScores[Analytics::INTERNAL_LINKS_COUNT] = 0;
            $ruleResults[self::RULE_INTERNAL_LINKS_MIN] = self::FAILED;
            $ruleInfo[self::RULE_INTERNAL_BACKLINKS_MIN] = "There is no internal links";
        } else {
            $ruleResults[self::RULE_INTERNAL_LINKS_MIN] = self::PASSED;
            $qualityScores[Analytics::INTERNAL_LINKS_COUNT] = $countBacklinks * $this->getConf(self::CONF_QUALITY_SCORE_INTERNAL_LINK_FACTOR, 1);;
        }

        /**
         * Broken Links
         */
        $brokenLinksCount = $this->stats[Analytics::INTERNAL_LINKS_BROKEN_COUNT];
        if ($brokenLinksCount > 2) {
            $qualityScores['no_' . Analytics::INTERNAL_LINKS_BROKEN_COUNT] = 0;
            $ruleResults[self::RULE_INTERNAL_BROKEN_LINKS_MAX] = self::FAILED;
            $ruleInfo[self::RULE_INTERNAL_BACKLINKS_MIN] = "There is {$brokenLinksCount} broken links";
        } else {
            $qualityScores['no_' . Analytics::INTERNAL_LINKS_BROKEN_COUNT] = $this->getConf(self::CONF_QUALITY_SCORE_INTERNAL_LINK_BROKEN_FACTOR, 2);;;
            $ruleResults[self::RULE_INTERNAL_BROKEN_LINKS_MAX] = self::PASSED;
        }

        /**
         * Changes, the more changes the better
         */
        $qualityScores[Analytics::EDITS_COUNT] = $this->stats[Analytics::EDITS_COUNT] * $this->getConf(self::CONF_QUALITY_SCORE_CHANGES_FACTOR, 0.25);;;


        /**
         * Rules that comes from the qc plugin
         * but are not yet fully implemented
         */

//        // 2 points for lot's of formatting
//        if ($this->stats[self::PLAINTEXT] && $this->stats['chars'] / $this->stats[self::PLAINTEXT] < 3) {
//            $ruleResults['manyformat'] = 2;
//        }
//
//        // 1/2 points for deeply nested quotations
//        if ($this->stats['quote_nest'] > 2) {
//            $ruleResults['deepquote'] += $this->stats['quote_nest'] / 2;
//        }
//
//        // 1/2 points for too many hr
//        if ($this->stats['hr'] > 2) {
//            $ruleResults['manyhr'] = ($this->stats['hr'] - 2) / 2;
//        }
//
//        // 1 point for too many line breaks
//        if ($this->stats['linebreak'] > 2) {
//            $ruleResults['manybr'] = $this->stats['linebreak'] - 2;
//        }
//
//        // 1 point for single author only
//        if (!$this->getConf('single_author_only') && count($this->stats['authors']) == 1) {
//            $ruleResults['singleauthor'] = 1;
//        }

        // Too much cdata (plaintext), see cdata
        // if ($len > 500) $statExport[self::QUALITY][self::ERROR]['plaintext']++;
        // if ($len > 500) $statExport[self::QUALITY][self::ERROR]['plaintext']++;
        //
        // // 1 point for formattings longer than 500 chars
        // $statExport[self::QUALITY][self::ERROR]['multiformat']

        /**
         * Quality Score
         */
        ksort($qualityScores);
        $qualityScoring = array();
        $qualityScoring["score"] = array_sum($qualityScores);
        $qualityScoring["scores"] = $qualityScores;


        /**
         * The rule that if broken will set the quality level to low
         */
        $brokenRules = array();
        foreach ($ruleResults as $ruleName => $ruleResult) {
            if ($ruleResult == self::FAILED) {
                $brokenRules[] = $ruleName;
            }
        }
        $ruleErrorCount = sizeof($brokenRules);
        if ($ruleErrorCount > 0) {
            $qualityResult = $ruleErrorCount . " quality rules errors";
        } else {
            $qualityResult = "All quality rules passed";
        }

        /**
         * Low level
         */
        $mandatoryRules = preg_split("/,/", $this->getConf(self::CONF_MANDATORY_QUALITY_RULES));
        $mandatoryRulesBroken = [];
        foreach ($mandatoryRules as $lowLevelRule) {
            if (in_array($lowLevelRule, $brokenRules)) {
                $mandatoryRulesBroken[] = $lowLevelRule;
            }
        }
        $lowLevel = false;
        if (sizeof($mandatoryRulesBroken) > 0) {
            $lowLevel = true;
        }
        LowQualityPage::setLowQualityPage($ID, $lowLevel);

        /**
         * Building the quality object in order
         */
        $quality["low"] = $lowLevel;
        if (sizeof($mandatoryRulesBroken) > 0) {
            ksort($mandatoryRulesBroken);
            $quality['failed_mandatory_rules'] = $mandatoryRulesBroken;
        }
        $quality["scoring"] = $qualityScoring;
        $quality["rules"][self::RESULT] = $qualityResult;
        if (!empty($ruleInfo)) {
            $quality["rules"]["info"] = $ruleInfo;
        }

        ksort($ruleResults);
        $quality["rules"]['details'] = $ruleResults;

        /**
         * Metadata
         */
        $this->metadata[Analytics::TITLE] = $meta['title'];
        $timestampCreation = $meta['date']['created'];
        $this->metadata[self::DATE_CREATED] = date('Y-m-d h:i:s', $timestampCreation);
        $timestampModification = $meta['date']['modified'];
        $this->metadata[Analytics::DATE_MODIFIED] = date('Y-m-d h:i:s', $timestampModification);
        $this->metadata['age_creation'] = round((time() - $timestampCreation) / 60 / 60 / 24);
        $this->metadata['age_modification'] = round((time() - $timestampModification) / 60 / 60 / 24);


        // get author info
        $changelog = new PageChangeLog($ID);
        $revs = $changelog->getRevisions(0, 10000);
        array_push($revs, $meta['last_change']['date']);
        $this->stats[Analytics::EDITS_COUNT] = count($revs);
        foreach ($revs as $rev) {
            $info = $changelog->getRevisionInfo($rev);
            if ($info['user']) {
                $this->stats['authors'][$info['user']] += 1;
            } else {
                $this->stats['authors']['*'] += 1;
            }
        }

        /**
         * Building the Top JSON in order
         */
        global $ID;
        $json = array();
        $json["id"] = $ID;
        $json['metadata'] = $this->metadata;
        ksort($statExport);
        $json[Analytics::STATISTICS] = $statExport;
        $json[Analytics::QUALITY] = $quality; // Quality after the sort to get them at the end


        /**
         * The result can be seen with
         * doku.php?id=somepage&do=export_combo_analysis
         */
        /**
         * Set the header for the export.php file
         */
        p_set_metadata($ID, array("format" =>
            array("combo_" . $this->getPluginComponent() => array("Content-Type" => 'application/json'))
        ));
        $json_encoded = json_encode($json, JSON_PRETTY_PRINT);

        $sqlite = Sqlite::getSqlite();
        if ($sqlite != null) {
            /**
             * Sqlite Plugin installed
             */
            $canonical = $this->metadata[UrlCanonical::CANONICAL_PROPERTY];
            if (empty($canonical)) {
                $canonical = $ID; // not null constraint unfortunately
            }
            $entry = array(
                'CANONICAL' => $canonical,
                'ANALYTICS' => $json_encoded,
                'ID' => $ID
            );
            $res = $sqlite->query("SELECT count(*) FROM PAGES where ID = ?", $ID);
            if ($sqlite->res2single($res) == 1) {
                // Upset not supported on all version
                //$upsert = 'insert into PAGES (ID,CANONICAL,ANALYTICS) values (?,?,?) on conflict (ID,CANONICAL) do update set ANALYTICS = EXCLUDED.ANALYTICS';
                $update = 'update PAGES SET CANONICAL = ?, ANALYTICS = ? where ID=?';
                $res = $sqlite->query($update, $entry);
            } else {
                $res = $sqlite->storeEntry('PAGES', $entry);
            }
            if (!$res) {
                LogUtility::msg("There was a problem during the upsert: {$sqlite->getAdapter()->getDb()->errorInfo()}");
            }
            $sqlite->res_close($res);
        }
        $this->doc .= $json_encoded;

    }

    /**
     */
    public function getFormat()
    {
        return Analytics::RENDERER_FORMAT;
    }

    public function internallink($id, $name = null, $search = null, $returnonly = false, $linktype = 'content')
    {

        LinkUtility::processInternalLinkStats($id, $this->stats);

    }

    public function externallink($url, $name = null)
    {
        $this->stats[Analytics::EXTERNAL_LINKS_COUNT]++;
    }

    public function header($text, $level, $pos)
    {
        $this->stats[Analytics::HEADERS_COUNT]['h' . $level]++;
        $this->headerId++;
        $this->stats[Analytics::HEADER_POSITION][$this->headerId] = 'h' . $level;

    }

    public function smiley($smiley)
    {
        if ($smiley == 'FIXME') $this->stats[self::FIXME]++;
    }

    public function linebreak()
    {
        if (!$this->tableopen) {
            $this->stats['linebreak']++;
        }
    }

    public function table_open($maxcols = null, $numrows = null, $pos = null) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->tableopen = true;
    }

    public function table_close($pos = null) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->tableopen = false;
    }

    public function hr()
    {
        $this->stats['hr']++;
    }

    public function quote_open() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->stats['quote_count']++;
        $this->quotelevel++;
        $this->stats['quote_nest'] = max($this->quotelevel, $this->stats['quote_nest']);
    }

    public function quote_close() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->quotelevel--;
    }

    public function strong_open() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->formattingBracket++;
    }

    public function strong_close() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->formattingBracket--;
    }

    public function emphasis_open() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->formattingBracket++;
    }

    public function emphasis_close() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->formattingBracket--;
    }

    public function underline_open() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->formattingBracket++;
    }

    public function underline_close() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->formattingBracket--;
    }

    public function cdata($text)
    {

        /**
         * It seems that you receive cdata
         * when emphasis_open / underline_open / strong_open
         * Stats are not for them
         */
        if (!$this->formattingBracket) return;

        $this->plainTextId++;

        /**
         * Length
         */
        $len = strlen($text);
        $this->stats[self::PLAINTEXT][$this->plainTextId]['len'] = $len;


        /**
         * Multi-formatting
         */
        if ($this->formattingBracket > 1) {
            $numberOfFormats = 1 * ($this->formattingBracket - 1);
            $this->stats[self::PLAINTEXT][$this->plainTextId]['multiformat'] += $numberOfFormats;
        }

        /**
         * Total
         */
        $this->stats[self::PLAINTEXT][0] += $len;
    }

    public function internalmedia($src, $title = null, $align = null, $width = null, $height = null, $cache = null, $linking = null)
    {
        $this->stats[Analytics::INTERNAL_MEDIAS_COUNT]++;
    }

    public function externalmedia($src, $title = null, $align = null, $width = null, $height = null, $cache = null, $linking = null)
    {
        $this->stats[Analytics::EXTERNAL_MEDIAS]++;
    }

    public function reset()
    {
        $this->stats = array();
        $this->metadata = array();
        $this->headerId = 0;
    }

    public function setMeta($key, $value)
    {
        $this->metadata[$key] = $value;
    }


}

