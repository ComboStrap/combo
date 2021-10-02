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

use ComboStrap\Analytics;
use ComboStrap\DatabasePage;
use ComboStrap\FsWikiUtility;
use ComboStrap\LogUtility;
use ComboStrap\Page;
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
 * set animal=foo
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
    const REPLICATE = "replicate";
    const ANALYTICS = "analytics";
    const SYNC = "sync";

    /**
     * register options and arguments
     * @param Options $options
     */
    protected function setup(Options $options)
    {
        $help = <<<EOF
Commands for the Combo Plugin.

If you want to use it for an animal farm, you need to set it first in a environment variable

Example:
```dos
set animal=foo
php ./bin/plugin.php combo --help
```
EOF;

        $options->setHelp($help);
        $options->registerOption('version', 'print version', 'v');
        $options->registerCommand(self::REPLICATE, "Replicate the data into the database");
        $options->registerCommand(self::ANALYTICS, "Start the analytics and export optionally the data");
        $options->registerOption(
            'namespaces',
            "If no namespace is given, the root namespace is assumed.",
            'n',
            true
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
        $options->registerCommand(self::SYNC, "Sync the database (ie delete the non-existent pages in the database)");

    }

    /**
     * The main entry
     * @param Options $options
     */
    protected function main(Options $options)
    {

        $namespaces = array_map('cleanID', $options->getArgs());
        if (!count($namespaces)) $namespaces = array(''); //import from top


        $depth = $options->getOpt('depth', 0);
        $cmd = $options->getCmd();
        if ($cmd === "") {
            $cmd = self::REPLICATE;
        }
        switch ($cmd) {
            case self::REPLICATE:
                $force = $options->getOpt('force', false);
                $this->replicate($namespaces, $force, $depth);
                break;
            case self::ANALYTICS:
                $output = $options->getOpt('output', '');
                //if ($output == '-') $output = 'php://stdout';
                $this->analytics($namespaces, $output, $depth);
                break;
            case self::SYNC:
                $this->sync();
                break;
            default:
                fwrite(STDERR, "Combo: Command unknown (" . $cmd . ")");
                $options->help();
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
        DatabasePage::processReplicationRequest(PHP_INT_MAX);

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
            $analytics = $page->getAnalytics();
            $data = $analytics->getData()->toArray();

            if (!empty($fileHandle)) {
                $statistics = $data[Analytics::STATISTICS];
                $row = array(
                    'id' => $id,
                    'backlinks' => $statistics[Analytics::INTERNAL_BACKLINK_COUNT],
                    'broken_links' => $statistics[Analytics::INTERNAL_LINK_BROKEN_COUNT],
                    'changes' => $statistics[Analytics::EDITS_COUNT],
                    'chars' => $statistics[Analytics::CHAR_COUNT],
                    'external_links' => $statistics[Analytics::EXTERNAL_LINK_COUNT],
                    'external_medias' => $statistics[Analytics::EXTERNAL_MEDIA_COUNT],
                    Analytics::H1 => $statistics[Analytics::HEADING_COUNT][Analytics::H1],
                    'h2' => $statistics[Analytics::HEADING_COUNT]['h2'],
                    'h3' => $statistics[Analytics::HEADING_COUNT]['h3'],
                    'h4' => $statistics[Analytics::HEADING_COUNT]['h4'],
                    'h5' => $statistics[Analytics::HEADING_COUNT]['h5'],
                    'internal_links' => $statistics[Analytics::INTERNAL_LINK_COUNT],
                    'internal_medias' => $statistics[Analytics::INTERNAL_MEDIA_COUNT],
                    'words' => $statistics[Analytics::WORD_COUNT],
                    'low' => $data[Analytics::QUALITY]['low']
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
        $sqlite = Sqlite::getSqlite();
        $res = $sqlite->query("select ID from pages");
        if (!$res) {
            throw new \RuntimeException("An exception has occurred with the alias selection query");
        }
        $res2arr = $sqlite->res2arr($res);
        $sqlite->res_close($res);
        foreach ($res2arr as $row) {
            $id = $row['ID'];
            if (!page_exists($id)) {
                echo 'Page does not exist on the file system. Deleted from the database (' . $id . ")\n";
                Page::createPageFromId($id)->deleteInDb();
            }
        }


    }
}
