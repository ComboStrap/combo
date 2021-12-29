<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */
if (!defined('DOKU_INC')) die();

use ComboStrap\AnalyticsDocument;
use ComboStrap\DatabasePageRow;
use ComboStrap\Event;
use ComboStrap\FsWikiUtility;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PageH1;
use ComboStrap\Sqlite;
use splitbrain\phpcli\Options;

/**
 * All dependency are loaded in plugin utility
 */
require_once(__DIR__ . '/ComboStrap/PluginUtility.php');

/**
 * The memory of the server 128 is not enough
 */
ini_set('memory_limit', '256M');


/**
 * Class cli_plugin_combo
 *
 * This is a cli:
 * https://www.dokuwiki.org/devel:cli_plugins#example
 *
 * Usage:
 *
 * ```
 * docker exec -ti $(CONTAINER) /bin/bash
 * ```
 * ```
 * set animal=animal-directory-name
 * php ./bin/plugin.php combo --help
 * ```
 * or via the IDE
 *
 *
 * Example:
 * https://www.dokuwiki.org/tips:grapher
 *
 */
class cli_plugin_combo extends DokuWiki_CLI_Plugin
{

    const METADATA_TO_DATABASE = "metadata-to-database";
    const ANALYTICS = "analytics";
    const METADATA_FRONTMATTER = "metadata-to-frontmatter";
    const SYNC = "sync";
    const PLUGINS_TO_UPDATE = "plugins-to-update";


    /**
     * register options and arguments
     * @param Options $options
     */
    protected function setup(Options $options)
    {
        $help = <<<EOF
ComboStrap Administrative Commands


Example:
  * Replicate all pages into the database
```bash
php ./bin/plugin.php combo metadata-to-database :
# or
php ./bin/plugin.php combo metadata-to-database /
```
  * Replicate only the page `:namespace:my-page`
```bash
php ./bin/plugin.php combo metadata-to-database :namespace:my-page
# or
php ./bin/plugin.php combo metadata-to-database /namespace/my-page
```

Animal: If you want to use it for an animal farm, you need to set first the animal directory name in a environment variable
```bash
set animal=animal-directory-name
```

EOF;

        $options->setHelp($help);
        $options->registerOption('version', 'print version', 'v');
        $options->registerCommand(self::METADATA_TO_DATABASE, "Replicate the file system metadata into the database");
        $options->registerCommand(self::ANALYTICS, "Start the analytics and export optionally the data");
        $options->registerCommand(self::PLUGINS_TO_UPDATE, "List the plugins to update");
        $options->registerCommand(self::METADATA_FRONTMATTER, "Replicate the file system metadata into the page frontmatter");
        $options->registerCommand(self::SYNC, "Delete the non-existing pages in the database");
        $options->registerArgument(
            'path',
            "The start path (a page or a directory). For all pages, type the root directory '/'",
            false
        );
        $options->registerOption(
            'output',
            "Optional, where to store the analytical data as csv eg. a filename.",
            'o', 'file');
        $options->registerOption(
            'force',
            "Replicate with force",
            'f', false);
        $options->registerOption(
            'dry',
            "Optional, dry-run",
            'd', false);


    }

    /**
     * The main entry
     * @param Options $options
     */
    protected function main(Options $options)
    {


        $args = $options->getArgs();


        $depth = $options->getOpt('depth', 0);
        $cmd = $options->getCmd();
        switch ($cmd) {
            case self::METADATA_TO_DATABASE:
                $startPath = $this->getStartPath($args);
                $force = $options->getOpt('force', false);
                $this->replicate($startPath, $force, $depth);
                break;
            case self::METADATA_FRONTMATTER:
                $startPath = $this->getStartPath($args);
                $this->frontmatter($startPath, $depth);
                break;
            case self::ANALYTICS:
                $startPath = $this->getStartPath($args);
                $output = $options->getOpt('output', '');
                //if ($output == '-') $output = 'php://stdout';
                $this->analytics($startPath, $output, $depth);
                break;
            case self::SYNC:
                $this->sync();
                break;
            case self::PLUGINS_TO_UPDATE:
                /**
                 * Endpoint:
                 * self::EXTENSION_REPOSITORY_API.'?fmt=php&ext[]='.urlencode($name)
                 * `http://www.dokuwiki.org/lib/plugins/pluginrepo/api.php?fmt=php&ext[]=`.urlencode($name)
                 */
                $pluginList = plugin_list('', true);
                /* @var helper_plugin_extension_extension $extension */
                $extension = $this->loadHelper('extension_extension');
                foreach ($pluginList as $name) {
                    $extension->setExtension($name);
                    if ($extension->updateAvailable()) {
                        echo "The extension $name should be updated";
                    }
                }
                break;
            default:
                if ($cmd !== "") {
                    fwrite(STDERR, "Combo: Command unknown (" . $cmd . ")");
                } else {
                    echo $options->help();
                }
                exit(1);
        }


    }

    /**
     * @param array $namespaces
     * @param bool $rebuild
     * @param int $depth recursion depth. 0 for unlimited
     */
    private function replicate($namespaces = array(), $rebuild = false, $depth = 0)
    {

        /**
         * Run as admin to overcome the fact that
         * anonymous user cannot see all links and backlinks
         */
        global $USERINFO;
        $USERINFO['grps'] = array('admin');
        global $INPUT;
        $INPUT->server->set('REMOTE_USER', "cli");

        $pages = FsWikiUtility::getPages($namespaces, $depth);

        $pageCounter = 0;
        $totalNumberOfPages = sizeof($pages);
        while ($pageArray = array_shift($pages)) {
            $id = $pageArray['id'];
            $page = Page::createPageFromId($id);

            $pageCounter++;
            $replicate = $page->getDatabasePage();
            if ($replicate->shouldReplicate() || $rebuild) {
                LogUtility::msg("The page {$id} ($pageCounter / $totalNumberOfPages) was replicated", LogUtility::LVL_MSG_INFO);
                $replicate->replicate();
            } else {
                LogUtility::msg("The page {$id} ($pageCounter / $totalNumberOfPages) was up to date", LogUtility::LVL_MSG_INFO);
            }

        }
        /**
         * Process all backlinks
         */
        echo "Processing Replication Request\n";
        Event::dispatchEvent(PHP_INT_MAX);

    }

    private function analytics($namespaces = array(), $output = null, $depth = 0)
    {

        $fileHandle = null;
        if (!empty($output)) {
            $fileHandle = @fopen($output, 'w');
            if (!$fileHandle) $this->fatal("Failed to open $output");
        }

        /**
         * Run as admin to overcome the fact that
         * anonymous user cannot see all links and backlinks
         */
        global $USERINFO;
        $USERINFO['grps'] = array('admin');
        global $INPUT;
        $INPUT->server->set('REMOTE_USER', "cli");

        $pages = FsWikiUtility::getPages($namespaces, $depth);


        if (!empty($fileHandle)) {
            $header = array(
                'id',
                'backlinks',
                'broken_links',
                'changes',
                'chars',
                'external_links',
                'external_medias',
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'internal_links',
                'internal_medias',
                'words',
                'score'
            );
            fwrite($fileHandle, implode(",", $header) . PHP_EOL);
        }
        $pageCounter = 0;
        $totalNumberOfPages = sizeof($pages);
        while ($pageArray = array_shift($pages)) {
            $id = $pageArray['id'];
            $page = Page::createPageFromId($id);


            $pageCounter++;
            echo "Analytics Processing for the page {$id} ($pageCounter / $totalNumberOfPages)\n";

            /**
             * Analytics
             */
            $analytics = $page->getAnalyticsDocument();
            $data = $analytics->getOrProcessContent()->toArray();

            if (!empty($fileHandle)) {
                $statistics = $data[AnalyticsDocument::STATISTICS];
                $row = array(
                    'id' => $id,
                    'backlinks' => $statistics[AnalyticsDocument::INTERNAL_BACKLINK_COUNT],
                    'broken_links' => $statistics[AnalyticsDocument::INTERNAL_LINK_BROKEN_COUNT],
                    'changes' => $statistics[AnalyticsDocument::EDITS_COUNT],
                    'chars' => $statistics[AnalyticsDocument::CHAR_COUNT],
                    'external_links' => $statistics[AnalyticsDocument::EXTERNAL_LINK_COUNT],
                    'external_medias' => $statistics[AnalyticsDocument::EXTERNAL_MEDIA_COUNT],
                    PageH1::PROPERTY_NAME => $statistics[AnalyticsDocument::HEADING_COUNT][PageH1::PROPERTY_NAME],
                    'h2' => $statistics[AnalyticsDocument::HEADING_COUNT]['h2'],
                    'h3' => $statistics[AnalyticsDocument::HEADING_COUNT]['h3'],
                    'h4' => $statistics[AnalyticsDocument::HEADING_COUNT]['h4'],
                    'h5' => $statistics[AnalyticsDocument::HEADING_COUNT]['h5'],
                    'internal_links' => $statistics[AnalyticsDocument::INTERNAL_LINK_COUNT],
                    'internal_medias' => $statistics[AnalyticsDocument::INTERNAL_MEDIA_COUNT],
                    'words' => $statistics[AnalyticsDocument::WORD_COUNT],
                    'low' => $data[AnalyticsDocument::QUALITY]['low']
                );
                fwrite($fileHandle, implode(",", $row) . PHP_EOL);
            }

        }
        if (!empty($fileHandle)) {
            fclose($fileHandle);
        }

    }


    private function sync()
    {
        LogUtility::msg("Sync started");
        $sqlite = Sqlite::createOrGetSqlite();
        $res = $sqlite->query("select ID from pages");
        if (!$res) {
            throw new \RuntimeException("An exception has occurred with the alias selection query");
        }
        $res2arr = $sqlite->res2arr($res);
        $sqlite->res_close($res);
        $counter=0;
        foreach ($res2arr as $row) {
            $counter++;
            $id = $row['ID'];
            if (!page_exists($id)) {
                echo 'Page does not exist on the file system. Deleted from the database (' . $id . ")\n";
                Page::createPageFromId($id)->getDatabasePage()->delete();
            }
        }
        LogUtility::msg("Sync finished ($counter pages checked)");


    }

    private function frontmatter($namespaces, $depth)
    {
        $pages = FsWikiUtility::getPages($namespaces, $depth);
        $pageCounter = 0;
        $totalNumberOfPages = sizeof($pages);
        $pagesWithChanges = [];
        $pagesWithError = [];
        $pagesWithOthers = [];
        $notChangedCounter = 0;
        while ($pageArray = array_shift($pages)) {
            $id = $pageArray['id'];
            $page = Page::createPageFromId($id);
            LogUtility::msg("Processing page {$id} ($pageCounter / $totalNumberOfPages) ", LogUtility::LVL_MSG_INFO);
            $pageCounter++;
            $message = syntax_plugin_combo_frontmatter::updateFrontmatter($page);

            switch ($message->getStatus()) {
                case syntax_plugin_combo_frontmatter::UPDATE_EXIT_CODE_NOT_CHANGED:
                    $notChangedCounter++;
                    break;
                case syntax_plugin_combo_frontmatter::UPDATE_EXIT_CODE_DONE:
                    $pagesWithChanges[] = $id;
                    break;
                case syntax_plugin_combo_frontmatter::UPDATE_EXIT_CODE_ERROR:
                    $pagesWithError[$id] = $message->getPlainTextContent();
                    break;
                default:
                    $pagesWithOthers[$id] = $message->getPlainTextContent();
                    break;

            }


        }

        echo "\n";
        echo "Result:\n";
        echo "$notChangedCounter pages without any frontmatter modifications\n";

        if (sizeof($pagesWithError) > 0) {
            echo "\n";
            echo "The following pages had errors";
            $pageCounter = 0;
            $totalNumberOfPages = sizeof($pagesWithError);
            foreach ($pagesWithError as $id => $message) {
                $pageCounter++;
                LogUtility::msg("Page {$id} ($pageCounter / $totalNumberOfPages) " . $message, LogUtility::LVL_MSG_ERROR);
            }
        } else {
            echo "No error\n";
        }

        if (sizeof($pagesWithChanges) > 0) {
            echo "\n";
            echo "The following pages had changed";
            $pageCounter = 0;
            $totalNumberOfPages = sizeof($pagesWithChanges);
            foreach ($pagesWithChanges as $id) {
                $pageCounter++;
                LogUtility::msg("Page {$id} ($pageCounter / $totalNumberOfPages) ", LogUtility::LVL_MSG_ERROR);
            }
        } else {
            echo "No changes\n";
        }

        if (sizeof($pagesWithOthers) > 0) {
            echo "\n";
            echo "The following pages had an other status";
            $pageCounter = 0;
            $totalNumberOfPages = sizeof($pagesWithOthers);
            foreach ($pagesWithOthers as $id => $message) {
                $pageCounter++;
                LogUtility::msg("Page {$id} ($pageCounter / $totalNumberOfPages) " . $message, LogUtility::LVL_MSG_ERROR);
            }
        }
    }

    private function getStartPath($args)
    {
        $sizeof = sizeof($args);
        switch ($sizeof) {
            case 0:
                fwrite(STDERR, "The start path is mandatory and was not given");
                exit(1);
            case 1:
                $startPath = $args[0];
                if (!in_array($startPath, [":", "/"])) {
                    // cleanId would return blank for a root
                    $startPath = cleanID($startPath);
                }
                break;
            default:
                fwrite(STDERR, "Too much arguments given $sizeof");
                exit(1);
        }
        return $startPath;
    }
}
