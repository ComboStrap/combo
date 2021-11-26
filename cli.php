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
    const REPLICATE = "replicate";
    const ANALYTICS = "analytics";
    const FRONTMATTER = "frontmatter";
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
php ./bin/plugin.php combo replicate :
# or
php ./bin/plugin.php combo replicate /
```
  * Replicate only the page `:namespace:my-page`
```bash
php ./bin/plugin.php combo replicate :namespace:my-page
# or
php ./bin/plugin.php combo replicate /namespace/my-page
```

Animal: If you want to use it for an animal farm, you need to set first the animal directory name in a environment variable
```bash
set animal=animal-directory-name
```

EOF;

        $options->setHelp($help);
        $options->registerOption('version', 'print version', 'v');
        $options->registerCommand(self::REPLICATE, "Replicate the file system metadata into the database");
        $options->registerCommand(self::ANALYTICS, "Start the analytics and export optionally the data");
        $options->registerCommand(self::PLUGINS_TO_UPDATE, "List the plugins to update");
        $options->registerCommand(self::FRONTMATTER, "Replicate the file system metadata into the page frontmatter");
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
        $sizeof = sizeof($args);
        switch ($sizeof){
            case 0:
                fwrite(STDERR, "The start path is mandatory and was not given");
                exit(1);
            case 1:
                $startPath = $args[0];
                if(!in_array($startPath,[":","/"])) {
                    // cleanId would return blank for a root
                    $startPath = cleanID($startPath);
                }
                break;
            default:
                fwrite(STDERR, "Too much arguments given $sizeof");
                exit(1);
        }

        $depth = $options->getOpt('depth', 0);
        $cmd = $options->getCmd();
        switch ($cmd) {
            case self::REPLICATE:
                $force = $options->getOpt('force', false);
                $this->replicate($startPath, $force, $depth);
                break;
            case self::FRONTMATTER:
                $this->frontmatter($startPath, $depth);
                break;
            case self::ANALYTICS:
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
                Page::createPageFromId($id)->getDatabasePage()->delete();
            }
        }


    }

    private function frontmatter($namespaces, $depth)
    {
        $pages = FsWikiUtility::getPages($namespaces, $depth);
        $pageCounter = 0;
        $totalNumberOfPages = sizeof($pages);
        while ($pageArray = array_shift($pages)) {
            $id = $pageArray['id'];
            $page = Page::createPageFromId($id);

            $pageCounter++;
            $message = syntax_plugin_combo_frontmatter::updateFrontmatter($page);
            LogUtility::msg("Page {$id} ($pageCounter / $totalNumberOfPages) " . $message->getPlainTextContent(), LogUtility::LVL_MSG_INFO);


        }
    }
}
